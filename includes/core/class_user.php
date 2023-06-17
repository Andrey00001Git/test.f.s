<?php

class User {

    // GENERAL

    public static function user_info($d) {
        // vars
        $user_id = isset($d['user_id']) && is_numeric($d['user_id']) ? $d['user_id'] : 0;
        $phone = isset($d['phone']) ? preg_replace('~\D+~', '', $d['phone']) : 0;
        // where
        if ($user_id) $where = "user_id='".$user_id."'";
        else if ($phone) $where = "phone='".$phone."'";
        else return [];

        $q = DB::query("SELECT user_id, first_name, last_name, phone, access, email, plot_id FROM users WHERE ".$where." LIMIT 1;") or die (DB::error());
        if ($row = DB::fetch_row($q)) {
            return [
                'id' => (int) $row['user_id'],
                'access' => (int) $row['access'],
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'phone' => $row['phone'],
                'email' => $row['email'],
                'plot_id' => $row['plot_id']
            ];
        } else {
            return [
                'id' => 0,
                'access' => 0,
                'first_name' => '',
                'last_name' => '',
                'phone' => 0,
                'email' => '',
                'plot_id' => ''
            ];
        }
    }

    public static function users_list($d = []) {
        // vars
        $search = isset($d['search']) && trim($d['search']) ? $d['search'] : '';
        $offset = isset($d['offset']) && is_numeric($d['offset']) ? $d['offset'] : 0;
        $limit = 20;
        $items = [];
        // where
        $where = [];
        if ($search) {
            $where[] = "phone LIKE '%".$search."%'";
            $where[] = "first_name LIKE '%".$search."%'";
            $where[] = "last_name LIKE '%".$search."%'";
            $where[] = "email LIKE '%".$search."%'";
        }
        $where = $where ? "WHERE ".implode(" OR ", $where) : "";
        // info
        $q = DB::query("SELECT plot_id, user_id, village_id, access, first_name, last_name, email, phone, phone_code, phone_attempts_code, phone_attempts_code, updated, last_login
            FROM users ".$where." ORDER BY plot_id+0 LIMIT ".$offset.", ".$limit.";") or die (DB::error());
        while ($row = DB::fetch_row($q)) {
            $items[] = [
                'id' => (int) $row['user_id'],
                'plot_id' => $row['plot_id'],
                'village_id' => $row['village_id'],
                'access' => $row['access'],
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'email' => $row['email'],
                'phone' => $row['phone'],
                'phone_code' => $row['phone_code'],
                'phone_attempts_code' => $row['phone_attempts_code'],
                'phone_attempts_sms' => $row['phone_attempts_code'],
                'updated' => date('Y/m/d', $row['updated']),
                'last_login' => $row['last_login'],
            ];
        }
        // paginator
        $q = DB::query("SELECT count(*) FROM users ".$where.";");
        $count = ($row = DB::fetch_row($q)) ? $row['count(*)'] : 0;
        $url = 'users';
        if ($search) $url .= '?search='.$search.'&';
        paginator($count, $offset, $limit, $url, $paginator);
        // output

        return ['items' => $items, 'paginator' => $paginator];
    }

    public static function users_fetch($d = []) {
        $info = User::users_list($d);
        HTML::assign('users', $info['items']);
        return ['html' => HTML::fetch('./partials/users_table.html'), 'paginator' => $info['paginator']];
    }

    public static function user_edit_window($d = []) {
        $user_id = isset($d['user_id']) && is_numeric($d['user_id']) ? $d['user_id'] : 0;
        HTML::assign('user', User::user_info(['user_id'=>$user_id]));
        return ['html' => HTML::fetch('./partials/user_edit.html')];
    }

    public static function user_edit_update($d = []) {
        // vars
        $user_id = isset($d['user_id']) && is_numeric($d['user_id']) ? $d['user_id'] : 0;
        $first_name = isset($d['first_name']) ? $d['first_name'] : '';
        $last_name = isset($d['last_name']) ? $d['last_name'] : '';
        $phone = isset($d['phone']) ? preg_replace('~\D+~', '', $d['phone']) : 0;
        $email = isset($d['email']) && trim($d['email']) ? strtolower(trim($d['email'])) : '';
        foreach (['first_name', 'last_name', 'phone', 'email'] as $val)
        {
            $check = self::check_empty_error($$val, $val);
            if ($check !== false) return $check;
        }
        $plot_id = isset($d['plot_id']) ? self::trim_explode_comma($d['plot_id']) : 0;
        $offset = isset($d['offset']) ? preg_replace('~\D+~', '', $d['offset']) : 0;
        // update
        if ($user_id) {
            $set = [];
            $set[] = "first_name='".$first_name."'";
            $set[] = "last_name='".$last_name."'";
            $set[] = "phone='".$phone."'";
            $set[] = "email='".$email."'";
            $set[] = "plot_id='".$plot_id."'";
            $set[] = "updated='".Session::$ts."'";
            $set = implode(", ", $set);
            DB::query("UPDATE users SET ".$set." WHERE user_id='".$user_id."' LIMIT 1;") or die (DB::error());
        } else {
            DB::query("INSERT INTO users (
                first_name,
                last_name,
                phone,
                email,
                plot_id,
                phone_code,
                access,
                updated
            ) VALUES (
                '".$first_name."',
                '".$last_name."',
                '".$phone."',
                '".$email."',
                '".$plot_id."',
                '1111',
                '1',
                '".Session::$ts."'
            );") or die (DB::error());
        }
        // output
        return User::users_fetch(['offset' => $offset]);
    }

    public static function delete_user($d = []) {
        // vars
        $user_id = isset($d['user_id']) && is_numeric($d['user_id']) ? $d['user_id'] : 0;
        $offset = isset($d['offset']) ? preg_replace('~\D+~', '', $d['offset']) : 0;
        // delete
        DB::query("DELETE FROM users WHERE user_id='".$user_id."' LIMIT 1;") or die (DB::error());
        // output
        return User::users_fetch(['offset' => $offset]);
    }

    public static function check_empty_error ($val, $name)
    {
        if (!$val) return error_response(1003, 'The parameter is missing or passed in the wrong format.', ['field' => $name]);
        else return false;
    }

    public static function trim_explode_comma($str)
    {
        $str = explode(',', $str);
        $new_str = '';
        foreach($str as $part)
        {
            if (trim($part)) $new_str .= trim($part).', ';
        }
        $new_str = substr($new_str, 0, -2);
        return $new_str;
    }

    public static function users_list_plots($number) {
        // vars
        $items = [];
        // info
        $q = DB::query("SELECT user_id, plot_id, first_name, email, phone
            FROM users WHERE plot_id LIKE '%".$number."%' ORDER BY user_id;") or die (DB::error());
        while ($row = DB::fetch_row($q)) {
            $plot_ids = explode(',', $row['plot_id']);
            $val = false;
            foreach($plot_ids as $plot_id) if ($plot_id == $number) $val = true;
            if ($val) $items[] = [
                'id' => (int) $row['user_id'],
                'first_name' => $row['first_name'],
                'email' => $row['email'],
                'phone_str' => phone_formatting($row['phone'])
            ];
        }
        // output
        return $items;
    }

}
