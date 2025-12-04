<?php

return [
    'title' => 'Support',
    'list' => 'Support tickets',
    'create' => 'Create support ticket',
    'detail' => 'Ticket detail',
    'not_found' => 'Ticket not found',
    'create_success' => 'Ticket created successfully',
    'message_sent' => 'Message sent successfully',
    'status_updated' => 'Status updated successfully',

    'status' => [
        'pending' => 'Pending',
        'open' => 'Open',
        'in_progress' => 'In progress',
        'resolved' => 'Resolved',
        'closed' => 'Closed',
    ],

    'priority' => [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
        'urgent' => 'Urgent',
    ],

    'reply_side' => [
        'customer' => 'Customer',
        'staff' => 'Staff',
    ],

    'subject' => 'Subject',
    'description' => 'Description',
    'priority_label' => 'Priority',
    'status_label' => 'Status',
    'created_by' => 'Created by',
    'assigned_to' => 'Assigned to',
    'created_at' => 'Created at',
    'updated_at' => 'Last updated',
    'messages' => 'Messages',
    'add_message' => 'Add message',
    'message_placeholder' => 'Enter your message...',
    'send' => 'Send',
    'update_status' => 'Update status',
    'no_tickets' => 'No tickets yet',
    'no_messages' => 'No messages yet',
    'telegram_notification_failed' => 'Unable to send Telegram notification',

    'validation' => [
        'subject_required' => 'Please enter the subject.',
        'subject_string' => 'Subject must be a string.',
        'subject_max' => 'Subject may not exceed :max characters.',
        'description_required' => 'Please enter the description.',
        'description_string' => 'Description must be a string.',
        'description_max' => 'Description may not exceed :max characters.',
        'message_required' => 'Please enter a message.',
        'message_string' => 'Message must be a string.',
        'message_max' => 'Message may not exceed :max characters.',
        'priority_integer' => 'Priority must be an integer.',
        'priority_invalid' => 'Priority is invalid.',
        'status_required' => 'Please choose a status.',
        'status_integer' => 'Status must be an integer.',
        'status_invalid' => 'Status is invalid.',
    ],
];

