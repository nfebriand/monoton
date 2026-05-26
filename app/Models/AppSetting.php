<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $fillable = ['key','value'];

    public static function get(string $key, $default = null)
    {
        $s = static::where('key',$key)->first();
        return $s ? $s->value : $default;
    }

    public static function set(string $key, $value): void
    {
        static::updateOrCreate(['key'=>$key],['value'=>$value]);
    }

    public static function allKeyed(): array
    {
        return static::all()->pluck('value','key')->toArray();
    }

    // Kunci kredit — digunakan untuk fitur blank jika diubah
    public static function kreditValid(): bool
    {
        $pembuat  = static::get('kredit_pembuat','Nanda Febriandy');
        $wa       = static::get('kredit_wa','082182778608');
        $telegram = static::get('kredit_telegram','@nfebriand');
        return $pembuat === 'Nanda Febriandy'
            && $wa === '082182778608'
            && $telegram === '@nfebriand';
    }
}
