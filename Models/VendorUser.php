<?php

namespace App\GP247\Plugins\MultiVendor\Models;

// use Illuminate\Auth\Authenticatable;
// use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Foundation\Auth\User as Authenticatable;
// use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class VendorUser extends Authenticatable
{
    use Notifiable, HasApiTokens;
    use \GP247\Core\Models\UuidTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $table = 'vendor_user';
    protected $guarded = [];
    protected $connection = GP247_DB_CONNECTION;
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];
    protected $appends = [
        'name',
    ];
    
    /**
     * Send the vendor password-reset e-mail.
     *
     * WHY rewritten (S1-2): the 1.x code read the body from a legacy S-Cart 2.x
     * e-mail template model class that does not exist in GP247 3.x, so "forgot
     * password" on /vendor_admin fataled. Mirrors
     * gp247_customer_sendmail_reset_notification(): same shop view and data, only
     * the reset URL points at the vendor route.
     *
     * @param  string $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $url = gp247_route_admin('vendor.password_reset', ['token' => $token]);
        $expire = config('auth.passwords.vendors.expire', config('auth.passwords.'.config('auth.defaults.passwords').'.expire'));
        $dataView = [
            'title' => gp247_language_render('email.forgot_password.title'),
            'reason_sendmail' => gp247_language_render('email.forgot_password.reason_sendmail'),
            'note_sendmail' => gp247_language_render('email.forgot_password.note_sendmail', ['count' => $expire]),
            'note_access_link' => gp247_language_render('email.forgot_password.note_access_link', ['reset_button' => gp247_language_render('email.forgot_password.reset_button'), 'url' => $url]),
            'reset_link' => $url,
            'reset_button' => gp247_language_render('email.forgot_password.reset_button'),
        ];
        $config = [
            'to' => $this->getEmailForPasswordReset(),
            'subject' => gp247_language_render('email.forgot_password.reset_button'),
        ];
        gp247_mail_send(gp247_shop_mail_view('email.shop_forgot_password'), $dataView, $config, []);
    }

    /*
    Full name
     */
    public function getNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;

    }


    protected static function boot()
    {
        parent::boot();
        // before delete() method call this
        static::deleting(function ($customer) {
            //
        }
        );
        //Uuid
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = gp247_generate_id('VUS');
            }
        });
    }


    /**
     * Update info customer
     * @param  [array] $dataUpdate
     * @param  [int] $id
     */
    public static function updateInfo($dataUpdate, $id)
    {
        $dataUpdate = gp247_clean($dataUpdate);
        $obj = self::find($id);
        return $obj->update($dataUpdate);
    }

    /**
     * Create new customer
     * @return [type] [description]
     */
    public static function createCustomer($dataInsert)
    {
        $dataClean = gp247_clean($dataInsert);
        $user = self::create($dataClean);
        return $user;
    }


    /**
     * Check customer has Check if the user is verified
     *
     * @return boolean
     */
    public function isVerified() {
        return ! is_null($this->email_verified_at)  || $this->provider_id ;
    }

    /**
     * Check customer need verify email
     *
     * @return boolean
     */
    public function hasVerifiedEmail() {
        return !$this->isVerified() && gp247_config('customer_verify');
    }

    /**
     * Create the vendor tables when missing (re-entrant; never drops — live
     * data survives install()/update() on an upgraded site).
     *
     * WHY this exists again: the S1-2 rewrite of this model (88ef93f) dropped the
     * original install()/uninstall(), so a fresh install / uninstall of the plugin
     * fataled on "Call to undefined method VendorUser::install()". Dev never saw
     * it because an upgraded site takes the adopt path, which skips
     * ExtensionModel::installExtension(). Guarded by MultiVendorPluginBootTest.
     *
     * @aidlc-unit multi-vendor-pro
     * @aidlc-story US-multi-vendor-pro-behaviour-tests
     */
    public function install(): void
    {
        $schema = Schema::connection($this->getConnectionName());
        if (!$schema->hasTable($this->table)) {
            $schema->create($this->table, function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('first_name', 100);
                $table->string('last_name', 100)->nullable();
                $table->string('email', 150)->nullable();
                $table->string('password', 100)->nullable();
                $table->string('postcode', 10)->nullable();
                $table->string('address1', 100)->nullable();
                $table->string('address2', 100)->nullable();
                $table->string('address3', 100)->nullable();
                $table->string('avatar', 255)->nullable();
                $table->string('country', 10)->nullable()->default('VN');
                $table->string('phone', 20)->nullable();
                $table->uuid('store_id')->index();
                $table->string('remember_token', 100)->nullable();
                $table->tinyInteger('status')->default(1);
                $table->timestamp('email_verified_at', 0)->nullable();
                $table->timestamps();
            });
        }
        if (!$schema->hasTable('vendor_password_resets')) {
            $schema->create('vendor_password_resets', function (Blueprint $table) {
                $table->string('email', 150)->index();
                $table->string('token', 255);
                $table->dateTime('created_at');
            });
        }
    }

    /**
     * Drop the vendor tables (uninstall only).
     */
    public function uninstall(): void
    {
        $schema = Schema::connection($this->getConnectionName());
        foreach ([$this->table, 'vendor_password_resets'] as $table) {
            if ($schema->hasTable($table)) {
                $schema->drop($table);
            }
        }
    }
}
