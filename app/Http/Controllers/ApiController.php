<?php

namespace App\Http\Controllers;

// use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Signup;
use App\Models\UserAddress;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Product;
use App\Models\Banner;
use App\Models\ReferralReward;
use App\Models\BonusPointTranction;
use App\Models\LuckyDraw;
use App\Models\BonusRewardWithdraw;
use App\Models\AddToCart;
use App\Models\DeliveryCharge;
use App\Models\ComplaintGrievance;
use App\Models\ProductReward;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use App\Helpers\Helper;
use Carbon\Carbon;

class ApiController extends Controller
{
    public function signup(Request $request)
    {
        try {
            $err = false;
            $page_title = "Signup";
            $validator = \Validator::make($request->all(), [
                "name" => "required",
                "mobile" => "required|digits:10",
                "email" => "required",
                "pin" => "required|digits:6",
                "password" => "required",
            ]);
            if ($validator->fails()) {
                $err = true;
                $resp['error'] = true;
                $resp['message'] = $validator->errors()->all();
            }

            $ref_id = $request->ref_id;

            $users = new Signup();
            $dup = Signup::where('mobile', '=', $request['mobile'])->get();
            if (count($dup->toArray()) > 0) {
                $err = true;
                $resp['error'] = true;
                $resp['message'] = "Duplicate Data";
            }

            if (!$err) {
                $users->name = $request['name'];
                $users->email = $request['email'];
                $users->mobile = $request['mobile'];
                $users->device_id = $request['device_id'];
                $users->google_id = $request['google_id'];
                $users->image_path = $request['image_path'];
                $users->pincode = $request['pin'];
                $users->usertype = 2;
                $users->userlevel = 10;
                $users->username = Str::random(10);
                $users->password = md5($request['password']);
                $users->pass = $request['password'];
                $users->refer_id = $request['ref_id'];
                $users->save();

                if ($request->hasFile('image')) {
                    $imageName = 'Sandip_ons' . time() . '.' . $request->image->extension();
                    $request->image->move('Upload/profile/', $imageName);
                    $users->image_path = asset("Upload/profile/" . $imageName);
                    $users->save();
                }

                $user = Signup::where('sl', '=', $users->sl)->first(['image_path', 'name', 'email', 'mobile', 'device_id', 'google_id', 'updated_at', 'created_at', 'sl']);
                $resp['error'] = false;
                $resp['data'] = $user;
                $resp['token'] = $user->createToken($user->email)->plainTextToken;
                $resp['message'] = "Signup Successfully.";
            }
        } catch (\Exception $e) {
            $resp['error'] = true;
            $resp['message'] = $e->getMessage();
        }
        return response($resp, 200);
    }

    public function update_profile(Request $request)
    {
        try {
            $err = false;
            $validator = \Validator::make($request->all(), [
                "id" => "required",
                "name" => "required",
                "mobile" => "required|digits:10",
            ]);
            if ($validator->fails()) {
                $err = true;
                $resp['error'] = true;
                $resp['message'] = $validator->errors()->all();
            }
            if (!$err) {
                $users = Signup::find($request->id);
                $users->name = $request->name;
                $users->mobile = $request->mobile;
                if($request->password)
                {
                    $users->password = md5($request->password);
                    $users->pass = $request->password;
                }
                $users->save();

                $user = Signup::where('sl', '=', $users->sl)->first(['image_path', 'name', 'email', 'mobile', 'device_id', 'google_id', 'updated_at', 'created_at', 'sl']);
                $resp['error'] = false;
                $resp['data'] = $user;
                $resp['token'] = $user->createToken($user->email)->plainTextToken;
                $resp['message'] = "Profile Update Successfully.";
            }
        } catch (\Exception $e) {
            $resp['error'] = true;
            $resp['message'] = $e->getMessage();
        }
        return response($resp, 200);
    }

    public function logoff()
    {
        try {
            auth()->Signup()->tokens()->delete();
            $resp['error'] = false;
            $resp['message'] = "Logged Out Successfully";
        } catch (\Exception $e) {
            $resp['error'] = true;
            $resp['message'] = $e->getMessage();
        }
        return response($resp, 200);
    }

    public function login(Request $request)
    {
        try {
            $request->validate(
                [
                    "username" => "required",
                    "password" => "required",
                ]
            );
            $username = $request->username;
            $password = $request->password;
            $test = Signup::where('username', '=', $username)->orWhere('mobile', '=', $username)->first();
            if ($test) {
                if ($test->actnum == "0") {
                    if ($test->password == md5($password)) {
                        $resp['error'] = false;
                        $resp['message'] = "Logged in Successfully";
                        $resp['user'] = $test;
                        $resp['token'] = $test->createToken($test->username)->plainTextToken;
                    } else {
                        $resp['error'] = true;
                        $resp['message'] = "Incorrect password";
                    }
                } else {
                    $resp['error'] = true;
                    $resp['message'] = "Account Deactivated";
                }
            } else {
                $resp['error'] = true;
                $resp['message'] = "Not registered";
            }
        } catch (\Exception $e) {
            $resp['error'] = true;
            $resp['message'] = $e->getMessage();
        }
        if ($resp['error']) {
            return response($resp, 200);
        } else {
            return response($resp, 200);
        }
    }

    public function Save_Deviceid(Request $request)
    {
        $resp = [
            'error' => false,
            'message' => '',
        ];
        try {
            $err = false;
            $validator = \Validator::make($request->all(), [
                "user_id" => "required",
                "device_id" => "required",
            ]);

            if ($validator->fails()) {
                $err = true;
                $resp['error'] = true;
                $resp['message'] = $validator->errors()->all();
            }

            if (!$err) {
                $User = Signup::find($request->user_id);
                if (!$User) {
                    throw new \Exception("User not found");
                }
                $User->device_id = $request->device_id;
                $User->save();

                $resp['error'] = false;
                $resp['message'] = "Update Successful";
            }
        } catch (\Exception $e) {
            $resp['error'] = true;
            $resp['message'] = $e->getMessage();
        }
        return response($resp, 200);
    }

    public function dashboard(Request $request)
    {
        try {
            $user = Signup::where('username', '=', $request->username)->first();

            if ($user) {
                $resp['error'] = false;
                $resp['message'] = "Data Found";
                $resp['data'] = $user;
            } else {
                $resp['error'] = true;
                $resp['message'] = "No Data Found";
            }
        } catch (\Exception $e) {
            $resp['error'] = true;
            $resp['message'] = $e->getMessage();
        }
        return response($resp, 200);
    }

    public function googleid_check($id, Request $request)
    {
        if ($id) {
            try {
                $data = Signup::where('google_id', '=', $id)->first();
                if ($data) {
                    $resp['error'] = false;
                    $resp['message'] = "Data Found";
                    $resp['data'] = $data;
                    $resp['token'] = $data->createToken($data->email)->plainTextToken;
                } else {
                    $resp['error'] = true;
                    $resp['message'] = "No Data Found";
                }
            } catch (\Exception $e) {
                $resp['error'] = true;
                $resp['message'] = $e->getMessage();
            }
        } else {
            $resp['error'] = true;
            $resp['message'] = "Google Id Not Found";
        }
        return response($resp, 200);
    }

    /* account deactivate */
    public function account_deactivate(Request $request)
    {
        try {
            $err = false;
            $resp = array();
            $validator = \Validator::make($request->all(), [
                "customer_id" => "required",
            ]);
            if ($validator->fails()) {
                $err = true;
                $resp['error'] = $err;
                $resp['message'] = $validator->errors()->all();
            }
            if (!$err) {

                $user = Signup::find($request->customer_id);
                $user->actnum = 1;
                $user->save();

                $resp['error'] = false;
                $resp['message'] = "Account Deactivate Successfully.";
            }
        } catch (\Exception $e) {
            $resp['error'] = true;
            $resp['message'] = $e->getMessage();
        }
        return response($resp, 200);
    }

}
