<?php

return [
    // ===== Admin / entity: appeals management =====
    'manage_title' => 'Manage Appeals',
    'th_id' => 'ID',
    'th_user' => 'User',
    'th_auction' => 'Auction',
    'th_subject' => 'Subject',
    'reason_label' => 'Reason:',
    'response_label' => 'Response',
    'response_placeholder' => 'Write your response here...',
    'none' => 'No appeals',

    // Workflow actions
    'forward_btn' => 'Forward to the entity',
    'reject_intake_label' => 'Reason for rejection',
    'reject_intake_btn' => 'Reject directly',
    'awaiting_entity' => 'Awaiting the entity’s decision.',
    'entity_decision_label' => 'Entity decision:',
    'entity_response_label' => 'Entity response:',
    'entity_response_field' => 'Entity response',
    'entity_response_placeholder' => 'Justify the decision...',
    'entity_no_action' => 'No action is required on this appeal right now.',
    'final_decision_label' => 'Final decision',
    'decision_approve' => 'Approve',
    'decision_reject' => 'Reject',
    'confirm_btn' => 'Confirm decision',

    // ===== Citizen: appeal on the auction page =====
    'tab' => 'Appeals',
    'auction_tab_title' => 'File an appeal against the result',
    'your_appeal_title' => 'Your appeal on this auction',
    'window_hint' => 'You may file a single appeal within :days days of the auction closing.',
    'subject' => 'Subject',
    'subject_placeholder' => 'Appeal title',
    'details' => 'Details',
    'details_placeholder' => 'Explain the reason for the appeal in detail...',
    'submit' => 'Submit appeal',
    'submitted_on' => 'Filed on',

    // ===== Citizen: my appeals (tracking) =====
    'submitted_list' => 'Submitted appeals',
    'admin_response' => 'Administration response:',
    'auction_ref' => 'Auction:',
    'none_submitted' => 'No appeals submitted',
    'file_from_auction_hint' => 'Appeals are filed from the auction page, after it closes.',

    // ===== Flash + error messages =====
    'flash_submitted' => 'Your appeal has been submitted successfully.',
    'flash_forwarded' => 'The appeal has been forwarded to the entity.',
    'flash_entity_decided' => 'The entity’s decision has been recorded.',
    'flash_responded' => 'The appeal has been resolved successfully.',
    'error_not_eligible' => 'You cannot file an appeal on this auction.',
    'error_already_filed' => 'You have already filed an appeal on this auction.',
    'error_invalid_transition' => 'This action cannot be performed on the appeal in its current state.',
    'error_invalid_decision' => 'Invalid decision.',
];
