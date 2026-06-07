<?php

namespace App\Enums;

/**
 * The platform-generated, electronically-signed documents (spec §10.2, §10.3).
 * Every generated PDF carries a QR code resolving to the public /verify route.
 */
enum DocumentType: string
{
    case CONDITION_BOOK = 'CONDITION_BOOK';   // كراسة الشروط (cahier des charges)
    case AWARD = 'AWARD';                       // وثيقة الترسية (award document)
    case PAYMENT_RECEIPT = 'PAYMENT_RECEIPT';  // إيصال الدفع
    case DELIVERY_REPORT = 'DELIVERY_REPORT';  // محضر التسليم

    public function label(): string
    {
        return __('enums.document_type.'.$this->value);
    }
}
