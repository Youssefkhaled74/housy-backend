<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\PreventDemoModeChanges;

class Wallet extends Model
{
    use PreventDemoModeChanges;
    
    protected $fillable = ['user_id', 'amount', 'payment_method', 'payment_details', 'type'];
    
    // Default type to 'in' for backwards compatibility
    protected $attributes = [
        'type' => 'in',
    ];

    public function user(){
    	return $this->belongsTo(User::class);
    }
    
    /**
     * Get the formatted transaction type
     * 
     * @return string
     */
    public function getTransactionTypeAttribute()
    {
        return $this->type === 'in' ? translate('Credit') : translate('Debit');
    }
    
    /**
     * Get the formatted amount with sign
     * 
     * @return string
     */
    public function getFormattedAmountAttribute()
    {
        if ($this->amount == 0) {
            return single_price(0);
        }
        
        // For backwards compatibility, if type is not set, use amount sign
        if (!$this->type) {
            return single_price($this->amount);
        }
        
        // If type is set, format based on type
        if ($this->type === 'in') {
            return '+ ' . single_price(abs($this->amount));
        } else {
            return '- ' . single_price(abs($this->amount));
        }
    }
}
