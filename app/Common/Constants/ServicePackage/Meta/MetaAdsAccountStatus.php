<?php

namespace App\Common\Constants\ServicePackage\Meta;

enum MetaAdsAccountStatus: int
{
    case ACTIVE = 1;
    case DISABLED = 2;
    case UNSETTLED = 3;
    case PENDING_REVIEW = 7;

}
