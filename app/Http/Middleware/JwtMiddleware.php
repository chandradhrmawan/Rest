<?php
namespace App\Http\Middleware;
use Closure;
use Exception;
use Firebase\JWT\JWT;
use App\Models\OmUster\TmUser;
use Firebase\JWT\ExpiredException;
class JwtMiddleware
{

    public function handle($request, Closure $next, $guard = null)
    {
        $token = $request->header("token");
        if(!$token) {
            // Unauthorized response if token not there
            return response()->json([
                'error' => 'Token not provided.'
            ], 401);
        }
        try {
        $credentials = JWT::decode($token, env('JWT_SECRET'), ['HS256']);
        } catch(ExpiredException $e) {
            $tes  = TmUser::where('API_TOKEN', $token)->update(['USER_STATUS' => '0']);
            return response()->json([
                'error' => 'Provided token is expired. Please Login'
            ], 400);
        } catch(Exception $e) {
            return response()->json([
                'error' => 'An error while decoding token.'
            ], 400);
        }
        $user = TmUser::where("USER_ID",$credentials->sub);
        // // Now let's put the user in the request class so that you can grab it from there
        $request->auth = $user;
        return $next($request);
        // return response($token);
    }

}
