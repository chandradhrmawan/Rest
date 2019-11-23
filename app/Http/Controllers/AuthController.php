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
            'exp' => time() + 30 // Expiration time
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
            // You wil probably have some sort of helpers or whatever
            // to make sure that you have the same response format for
            // differents kind of responses. But let's return the
            // below respose for now.
            return response()->json([
                'error' => 'Invalid Username / Password'
            ], 400);
        }
        // Verify the password and generate the token
        if (Hash::check($this->request->input('USER_PASSWD'), $user["user_passwd"])) {
            $cek      = TmUser::where('USER_NAME', $this->request->input('USER_NAME'))->first();
            $header   = TmUser::where('USER_NAME', $this->request->input('USER_NAME'))->select("user_id", "user_name", "user_role","user_nik","user_branch_id", "user_full_name", "api_token")->first();
            $brnc_id  = json_decode(json_encode($header), TRUE);
            $detail   = DB::connection('mdm')->table('TM_BRANCH')->where('BRANCH_ID','=',$brnc_id['user_branch_id'])->get();

            if ($cek["user_status"] == "1") {
              return response()->json(["message"=>"You Already Login","data"=>$brnc_id]);
            } else {
              $tes  = TmUser::where('USER_NAME', $this->request->input('USER_NAME'))->update(['API_TOKEN' => $this->jwt($user), 'USER_STATUS' => '1']);
              return response()->json(["message"=>"Login Success", "user"=>$brnc_id,"branch"=>$detail]);
            }
        }
        // Bad Request response
        return response()->json([
            'error' => 'Invalid Username / Password'
        ], 400);

        // return response($user);

    }
}
