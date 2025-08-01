<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use URL;
use DB;
use Hash;
use App\Models\BusinessSetting;
use App\Models\User;
use MehediIitdu\CoreComponentRepository\CoreComponentRepository;
use Artisan;
use Session;
use ZipArchive;

class InstallController extends Controller
{
    public function step0() {
        $this->writeEnvironmentFile('APP_URL', URL::to('/'));
        return view('installation.step0');
    }

    public function step1() {
        $permission['curl_enabled']           = function_exists('curl_version');
        $permission['db_file_write_perm']     = is_writable(base_path('.env'));
        $permission['routes_file_write_perm'] = is_writable(base_path('app/Providers/RouteServiceProvider.php'));
        return view('installation.step1', compact('permission'));
    }

    public function step2() {
        return view('installation.step2');
    }

    public function step3($error = "") {
        CoreComponentRepository::instantiateShopRepository();
        if($error == ""){
            return view('installation.step3');
        }else {
            return view('installation.step3', compact('error'));
        }
    }

    public function step4() {
        return view('installation.step4');
    }

    public function step5() {
        return view('installation.step5');
    }

    public function purchase_code(Request $request) {
        if (\App\Utility\CategoryUtility::create_initial_category($request->purchase_code) == false) {
            flash("Sorry! The purchase code you have provided is not valid.")->error();
            return back();
        }
        if ($request->system_key == null) {
            flash("Sorry! The System Key required")->error();
            return back();
        }
        Session::put('purchase_code', $request->purchase_code);
        $this->writeEnvironmentFile('SYSTEM_KEY', $request->system_key);
        return redirect('step3');
    }

    public function system_settings(Request $request) {
        $businessSetting = BusinessSetting::where('type', 'system_default_currency')->first();
        $businessSetting->value = $request->system_default_currency;
        $businessSetting->save();

        $businessSetting = BusinessSetting::where('type', 'home_default_currency')->first();
        $businessSetting->value = $request->system_default_currency;
        $businessSetting->save();

        $this->writeEnvironmentFile('APP_NAME', $request->system_name);
        Artisan::call('key:generate');

        $user = new User;
        $user->name      = $request->admin_name;
        $user->email     = $request->admin_email;
        $user->password  = Hash::make($request->admin_password);
        $user->user_type = 'admin';
        $user->email_verified_at = date('Y-m-d H:m:s');
        $user->save();

        //Assign Super-Admin Role
        $user->assignRole(['Super Admin']);

        $previousRouteServiceProvier = base_path('app/Providers/RouteServiceProvider.php');
        $newRouteServiceProvier      = base_path('app/Providers/RouteServiceProvider.txt');
        copy($newRouteServiceProvier, $previousRouteServiceProvier);
        //sleep(5);

        if (Session::has('purchase_code')) {
            $business_settings = new BusinessSetting;
            $business_settings->type = 'purchase_code';
            $business_settings->value = Session::get('purchase_code');
            $business_settings->save();
            Session::forget('purchase_code');
        }
        Artisan::call('optimize:clear');
        return view('installation.step6');
    }
    public function database_installation(Request $request) {

        if(self::check_database_connection($request->DB_HOST, $request->DB_DATABASE, $request->DB_USERNAME, $request->DB_PASSWORD)) {
            $path = base_path('.env');
            if (file_exists($path)) {
                foreach ($request->types as $type) {
                    $this->writeEnvironmentFile($type, $request[$type]);
                }
                return redirect('step4');
            }else {
                return redirect('step3');
            }
        }else {
            return redirect('step3/database_error');
        }
    }

    public function import_sql() {
        $sql_path = base_path('shop.sql');
        DB::unprepared(file_get_contents($sql_path));
        return redirect('step5');
    }

    public function import_sql_with_demo() {
        $sql_path = base_path('shop.sql');
        DB::unprepared(file_get_contents($sql_path));

        // import sql
        $sql_path = base_path('public/demo.sql');
        DB::unprepared(file_get_contents($sql_path));

        // extract images
        $zip = new ZipArchive;
        $zip->open(base_path('public/uploads.zip'));
        $zip->extractTo('public/uploads/all/');
        flash(translate('Demo data uploaded successfully'))->success();
        return redirect('step5');
    }

    function check_database_connection($db_host = "", $db_name = "", $db_user = "", $db_pass = "") {

        if(@mysqli_connect($db_host, $db_user, $db_pass, $db_name)) {
            return true;
        }else {
            return false;
        }
    }

    public function writeEnvironmentFile($type, $val) {
        $path = base_path('.env');
        if (file_exists($path)) {
            $val = '"'.trim($val).'"';
            if(is_numeric(strpos(file_get_contents($path), $type)) && strpos(file_get_contents($path), $type) >= 0){
                file_put_contents($path, str_replace(
                    $type.'="'.env($type).'"', $type.'='.$val, file_get_contents($path)
                ));
            }
            else{
                file_put_contents($path, file_get_contents($path)."\r\n".$type.'='.$val);
            }
        }
    }
}
