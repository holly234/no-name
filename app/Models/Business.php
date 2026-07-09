<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
    protected $fillable = [
        'owner_id',
        'name',
        'slug',
        'category',
        'email',
        'phone',
        'website',
        'description',
        'webhook_secret',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class)->withPivot('role')->withTimestamps();
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function connectedAccounts()
    {
        return $this->hasMany(ConnectedAccount::class);
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    public function aiSetting()
    {
        return $this->hasOne(AiSetting::class);
    }

    public function faqs()
    {
        return $this->hasMany(Faq::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function businessRules()
    {
        return $this->hasMany(BusinessRule::class);
    }

    public function automationLogs()
    {
        return $this->hasMany(AutomationLog::class);
    }
}
