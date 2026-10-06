<?php

return [
    'required' => 'The :attribute field is required.',
    'string' => 'The :attribute field must be a string.',
    'email' => 'The :attribute field must be a valid email address.',
    'max' => [
        'string' => 'The :attribute field must not exceed :max characters.',
        'file' => 'The file size must not exceed :max kilobytes.',
    ],
    'post_too_large' => 'The request is larger than the server allows. Ask the admin to raise Nginx client_max_body_size and PHP post_max_size.',
    'post_too_large_detail' => 'Request size (:content) exceeds server limit. post_max_size=:post_max, upload_max_filesize=:upload_max.',
    'date' => 'The :attribute field must be a valid date.',
    'exists' => 'The selected :attribute is invalid.',
    'integer' => 'The :attribute field must be an integer.',
    'array' => 'The :attribute field must be an array.',
    'role' => [
        'name_required' => 'Role name is required.',
        'permissions_required' => 'At least one permission must be selected.',
        'permissions_min' => 'At least one permission must be selected.',
    ],
    'user' => [
        'name_required' => 'User name is required.',
        'email_required' => 'Email is required.',
        'email_format' => 'Invalid email format.',
        'email_unique' => 'This email is already in use.',
        'password_required' => 'Password is required.',
        'password_confirmed' => 'Password confirmation does not match.',
        'role_required' => 'A role must be selected for the user.',
        'department_exists' => 'The selected organizational unit does not exist.',
        'cannot_assign_admin' => 'You cannot assign the system administrator role.',
        'cannot_demote_self' => 'You cannot change your role to one lower than system administrator.',
        'cannot_remove_own_user_edit' => 'You cannot turn off user editing on your own account.',
        'permission_invalid' => 'One of the selected permissions is unknown.',
    ],
    'attributes' => [
        'title' => 'Transaction title',
        'description' => 'Description',
        'department_id' => 'Organizational unit',
        'folder_id' => 'Folder',
        'transaction_type_id' => 'Transaction type',
        'transaction_date' => 'Transaction date',
        'notes' => 'Notes',
        'files' => [
            '*' => 'Document',
        ],
    ],
];
