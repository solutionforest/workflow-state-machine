<?php

namespace WorkflowStateMachine\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use WorkflowStateMachine\Traits\HasWorkflowStates;

class CustomStatusArrayModel extends Model
{
    use HasWorkflowStates;

    protected $table = 'custom_status_array_models';

    protected $fillable = ['name', 'order_status'];

    protected $status_column = 'order_status';

    protected $status_array = [
        'pending' => 'Pending Payment',
        'paid' => 'Payment Received',
        'processing' => 'Processing Order',
        'shipped' => 'Shipped',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
    ];
}
