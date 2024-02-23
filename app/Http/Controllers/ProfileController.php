<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\File;

class ProfileController extends Controller
{
    //
    public function index()
    {
        $u = Auth::user();

        if (!$u) {
            return redirect(route("login"));
        }

        return view("profile", compact("u"));
    }

    public function update_profile(Request $req)
    {
        $u = Auth::user();

        if (!$u) {
            return redirect(route("login"));
        }

        $req->validate([
            "fullname"  => "required|string|min:1|max:255|regex:/^[a-zA-Z ]+$/",
            "gender"    => "required|string|in:M,F",
            "phone"     => "required|regex:/^\+?[0-9]{9,15}$/|unique:users,phone,{$u->id}",
            "username"  => "required|string|min:4|max:255|unique:users,username,{$u->id}|regex:/^[a-zA-Z0-9\.\_]+$/",
            "email"     => "required|email|max:255|unique:users,email,{$u->id}",
            "photo"     => [
                File::types(["jpg", "png", "jpeg", "gif", "bmp"])->min("1kb")->max("2mb")
            ]
        ]);

        if (!password_verify($req->password, $u->password)) {
            return redirect(route("profile") . "?edit_profile=1")->withErrors("Password is incorrect");
        }

        if ($req->hasFile("photo")) {
            $photo = $req->file("photo");

            if (!$photo->isValid()) {
                return redirect(route("profile") . "?edit_profile=1")->withErrors(["Invalid photo"]);
            }

            $photo_ext = $photo->extension();
            $hash = hash_file("sha256", $photo->getPathName(), true);
            $name = bin2hex($hash) . "." . $photo_ext;

            if (!move_uploaded_file($photo->getPathname(), public_path("assets/img") . "/" . $name)) {
                return redirect(route("profile") . "?edit_profile=1")->withErrors(["Failed to move upload file"]);
            }

            $u->photo = $hash;
            $u->photo_ext = $photo_ext;
        }

        $u->fullname = $req->fullname;
        $u->gender = $req->gender;
        $u->phone = $req->phone;
        $u->username = $req->username;
        $u->email = $req->email;

        $u->save();

        return redirect(route("profile"));
    }

    public function update_password(Request $req)
    {
        $u = Auth::user();

        if (!$u) {
            return redirect(route("login"));
        }

        $req->validate([
            "old_password" => "required|string|min:8",
            "new_password" => "required|string|min:8",
            "new_password2" => "required|string|min:8"
        ]);

        if (!password_verify($req->old_password, $u->password)) {
            return redirect(route("profile") . "?change_password=1")->withErrors(["Old password is incorrect"]);
        }

        if ($req->new_password !== $req->new_password2) {
            return redirect(route("profile") . "?change_password=1")->withErrors(["Confirm new password does not match with new password"]);
        }

        $u->password = password_hash($req->new_password, PASSWORD_BCRYPT);

        $u->save();

        return redirect(route("profile"));
    }
}
