<?php
namespace App\Http\Controllers;
use Validator;
use App\Models\OmUster\TmUser;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Firebase\JWT\ExpiredException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

use Laravel\Lumen\Routing\Controller as BaseController;
class AuthController extends BaseController
{
    /**
     * The request instance.
     *
     * @var \Illuminate\Http\Request
     */
    private $request;
    /**
     * Create a new controller instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function __construct(Request $request) {
        $this->request = $request;
    }
    /**
     * Create a new token.
     *
     * @param  \App\TmUser   $user
     * @return string
     */
    protected function jwt(TmUser $user) {
        $payload = [
            'iss' => "bearer", // Issuer of the token
            'sub' => $user->user_id, // Subject of the token
            'exp' => time() + 60*60*12 // Expiration time
        ];

        // As you can see we are passing `JWT_SECRET` as the second parameter that will
        // be used to decode the token in the future.
        return JWT::encode($payload, env('JWT_SECRET'));
    }
    /**
     * Authenticate a user and return the token if the provided credentials are correct.
     *
     * @param  \App\TmUser   $user
     * @return mixed
     */
    public function authenticate(TmUser $user) {
        $this->validate($this->request, [
            'USER_NAME'     => 'required',
            'USER_PASSWD'   => 'required'
        ]);
        // Find the user by email
        $user = TmUser::where('USER_NAME', $this->request->input('USER_NAME'))->first();
        if (!$user) {
            return response()->json([
                'message' => 'Invalid Username / Password'
            ], 400);
        }
        // Verify the password and generate the token
        date_default_timezone_set('GMT');
        $time = date('H:i:s',strtotime('+7 hour',strtotime(date("h:i:s"))));
        if (Hash::check($this->request->input('USER_PASSWD'), $user["user_passwd"])) {
            $cek      = TmUser::where('USER_NAME', $this->request->input('USER_NAME'))->first();
            $header   = TmUser::where('USER_NAME', $this->request->input('USER_NAME'))->select("user_id", "user_name", "user_role","user_nik","user_branch_id", "user_full_name", "api_token", "user_active","user_branch_code")->first();
            $brnc_id  = json_decode(json_encode($header), TRUE);
            $detail   = DB::connection('mdm')->table('TM_BRANCH')->where([['BRANCH_ID','=',$brnc_id['user_branch_id']],['BRANCH_CODE','=',$brnc_id['user_branch_code']]])->get();

            if (empty($detail)) {
              return response()->json(["message"=>"Branch Not Found"], 400);
            }

            $data = DB::connection('omuster')->table('TM_USER')->where('USER_NAME', $this->request->input('USER_NAME'))->get();
            $token = $data[0]->api_token;
            if (empty($token)) {
              $b = TmUser::where('USER_NAME', $this->request->input('USER_NAME'))->update(['API_TOKEN' => $this->jwt($user), 'USER_STATUS' => '1','USER_ACTIVE' => $time]);
              $hdr  = TmUser::where('USER_NAME', $this->request->input('USER_NAME'))->select("user_id", "user_name", "user_role","user_nik","user_branch_id", "user_full_name", "api_token", "user_active")->first();
              return response()->json(["message"=>"Login Success", "user"=>$hdr,"branch"=>$detail]);
            }
            try {
            $credentials = JWT::decode($token, env('JWT_SECRET'), ['HS256']);
            } catch(ExpiredException $e) {
                $a = TmUser::where('API_TOKEN', $token)->update(['USER_STATUS' => '0','API_TOKEN' => ""]);
                $b = TmUser::where('USER_NAME', $this->request->input('USER_NAME'))->update(['API_TOKEN' => $this->jwt($user), 'USER_STATUS' => '1','USER_ACTIVE' => $time]);
                $hdr  = TmUser::where('USER_NAME', $this->request->input('USER_NAME'))->select("user_id", "user_name", "user_role","user_nik","user_branch_id", "user_full_name", "api_token")->first();
                return response()->json(["message"=>"Login Success", "user"=>$hdr,"branch"=>$detail]);
            } catch(Exception $e) {
                return response()->json(["message"=>"Error Token"], 400);
            }
            return response()->json(["message"=>"User Already Login"],400);

          } else {
          // Bad Request response
          return response()->json([
            'message' => 'Invalid Username / Password'
          ], 400);
        }

    }
}
