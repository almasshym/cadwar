<?php

namespace App\Http\Controllers;

use App\EmailConfirmCode;
use App\Helpers\IsLocalhost;
use App\Helpers\Randomizer;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->all();
        list($validated, $email, $password, $validateErrors) = $this->validateData($data);

        if ($validated === false) {
            return $this->makeError($validateErrors);
        }
        $user = User::where('email', $email)->first();
        if (isset($user)) {
            return $this->makeError("email уже существует");
        }
        $user = new User;
        $user->email = $email;
        $user->password = Hash::make($password);
        $user->save();

        $code = Randomizer::GetString(50);
        $sent = $this->sendVerificationCodeToEmail($code, $email);

        if ($sent !== true) {
            return $this->makeError('Произошла ошибка на стороне сервера. Не удалось отправить код на почту', 500);
        }
        $created = EmailConfirmCode::create(['code' => $code, 'user_id' => $user->id]);
        if ($created === false) {
            return $this->makeError('Произошла ошибка на стороне сервера. Не удалось создать запись в базе', 500);
        }
        return response("OK", 200);
    }

    public function code(Request $request, $code)
    {
        $confirmCode = EmailConfirmCode::where('code', $code)->first();
        if (isset($confirmCode)) {
            $user = User::find($confirmCode->user_id);
            $user->email_verified = true;
            $user->is_active = true;
            $activated = $user->save();
            if ($activated) {
                return redirect('/#/login');
            }
        }
        return response(400);
    }

    public function login(Request $request)
    {
        $data = $request->all();
        $user = User::where([
            ['email', '=', $data['email']],
            ['password', '=', Hash::make($data['password'])],
        ])->firstOrFail();
        return "login";
    }

    public function reset(Request $request)
    {
        return "reset";
    }
    private function validateData($data)
    {
        $validated = true;
        $email = $data['email'];
        $password = $data['password'];
        $confirm = $data['confirm'];
        $validateErrors = [];
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $validated = false;
            $validateErrors[] = "email не прошел валидацию";
        }
        if (strlen($password) < 6) {
            $validated = false;
            $validateErrors[] = "пароль слишком короткий";
        }
        if ($password !== $confirm) {
            $validated = false;
            $validateErrors[] = "подтверждение пароля не совпадает";
        }
        return array($validated, $email, $password, $validateErrors);
    }
    public function sendVerificationCodeToEmail($code, $email)
    {
        $isLocalhost = IsLocalhost::Check();

        if ($isLocalhost === true) {
            return true;
        }
        $httpOrigin = 'https://cadwar.karnurmax.kz';
        $message = "https://cadwar.karnurmax.kz/auth/code/$code";

        // $headers = 'MIME-Version: 1.0' . "\r\n";
        // $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";

        mail($email, 'Регистрация', $message);
        return true;

    }
}
