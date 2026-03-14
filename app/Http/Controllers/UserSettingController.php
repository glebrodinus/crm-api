<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\UserSetting;

class UserSettingController extends Controller
{
    public function show()
    {
        $settings = Auth::user()->settings;

        return $this->success('User settings retrieved successfully', $settings);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'minimum_profit_amount' => 'nullable|numeric|min:0',
            'target_margin_percent' => 'nullable|numeric|min:0|max:100',
            'quote_validity_days' => 'nullable|integer|min:1',
        ]);

        $settings = UserSetting::updateOrCreate(
            ['user_id' => Auth::id()],
            $data
        );

        if (!$settings->wasChanged()) {
            return $this->success('No changes to update', $settings);
        }
        
        return $settings
            ? $this->success('User settings updated successfully', $settings)
            : $this->error('Failed to update user settings', [], 500);
    }
}