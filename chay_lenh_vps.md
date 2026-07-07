root@2511240624183887hovanvinh:/var/www/html/nhm-ads-agency-adsviet# php artisan tinker --execute 'dump(App\Models\PlatformSetting::query()->where("platform",2)->get()->map(fn($s)=>["id"=>(string)$s->id,"name"=>$s->name,"disabled"=>(bool)$s->disabled,"business_manager_id"=>$s->config["business_manager_id"]??null,"bm_id"=>$s->config["bm_id"]??null,"sync_all_accessible_businesses"=>$s->config["sync_all_accessible_businesses"]??null])->toArray());'
array:1 [
  0 => array:6 [
    "id" => "67447573506426862"
    "name" => "Adviet Agency FB"
    "disabled" => false
    "business_manager_id" => "1537217683931546"
    "bm_id" => null
    "sync_all_accessible_businesses" => true
  ]
] // vendor/psy/psysh/src/ExecutionClosure.php(41) : eval()'d code:1
root@2511240624183887hovanvinh:/var/www/html/nhm-ads-agency-adsviet# php artisan tinker --execute '$bm="1310584400254306"; dump(["bm"=>App\Models\MetaBusinessManager::query()->where("bm_id",$bm)->first(["bm_id","parent_bm_id","name","is_primary","is_direct_access","hidden_at","last_synced_at"])?->toArray(),"children"=>App\Models\MetaBusinessManager::query()->where("parent_bm_id",$bm)->get(["bm_id","parent_bm_id","name","hidden_at","last_synced_at"])->toArray()]);'
array:2 [
  "bm" => array:7 [
    "bm_id" => "1310584400254306"
    "parent_bm_id" => null
    "name" => "01 HYHD SC27-Vip2"
    "is_primary" => false
    "is_direct_access" => true
    "hidden_at" => null
    "last_synced_at" => "2026-06-02T09:00:06.000000Z"
  ]
  "children" => array:15 [
    0 => array:5 [
      "bm_id" => "560097219873387"
      "parent_bm_id" => "1310584400254306"
      "name" => "Energy Harbor CORP"
      "hidden_at" => null
      "last_synced_at" => "2026-05-30T15:42:13.000000Z"
    ]
    1 => array:5 [
      "bm_id" => "1006144757255711"
      "parent_bm_id" => "1310584400254306"
      "name" => "Lolo 33"
      "hidden_at" => null
      "last_synced_at" => "2026-05-30T15:42:16.000000Z"
    ]
    2 => array:5 [
      "bm_id" => "849199616403145"
      "parent_bm_id" => "1310584400254306"
      "name" => "Lolo 35"
      "hidden_at" => null
      "last_synced_at" => "2026-05-30T15:42:15.000000Z"
    ]
    3 => array:5 [
      "bm_id" => "1007086963671215"
      "parent_bm_id" => "1310584400254306"
      "name" => "Lolo 32"
      "hidden_at" => null
      "last_synced_at" => "2026-05-30T15:42:15.000000Z"
    ]
    4 => array:5 [
      "bm_id" => "1670049280129337"
      "parent_bm_id" => "1310584400254306"
      "name" => "Tele Agency "
      "hidden_at" => null
      "last_synced_at" => "2026-05-30T15:42:14.000000Z"
    ]
    5 => array:5 [
      "bm_id" => "2519847018167081"
      "parent_bm_id" => "1310584400254306"
      "name" => "Lolo 28"
      "hidden_at" => null
      "last_synced_at" => "2026-05-30T15:42:16.000000Z"
    ]
    6 => array:5 [
      "bm_id" => "1075843796747536"
      "parent_bm_id" => "1310584400254306"
      "name" => "Adviet Agency LCC USA"
      "hidden_at" => null
      "last_synced_at" => "2026-05-30T15:42:17.000000Z"
    ]
    7 => array:5 [
      "bm_id" => "1060240265533205"
      "parent_bm_id" => "1310584400254306"
      "name" => "Adviet Agency LLC USA"
      "hidden_at" => null
      "last_synced_at" => "2026-05-30T15:42:17.000000Z"
    ]
    8 => array:5 [
      "bm_id" => "9793342250694664"
      "parent_bm_id" => "1310584400254306"
      "name" => "Alistar WORK CO, LTD"
      "hidden_at" => null
      "last_synced_at" => "2026-06-02T09:00:35.000000Z"
    ]
    9 => array:5 [
      "bm_id" => "601420297640214"
      "parent_bm_id" => "1310584400254306"
      "name" => "Bm 25 limit 5m8"
      "hidden_at" => null
      "last_synced_at" => "2026-05-30T15:42:14.000000Z"
    ]
    10 => array:5 [
      "bm_id" => "2004071806661071"
      "parent_bm_id" => "1310584400254306"
      "name" => "Metamask wallet"
      "hidden_at" => null
      "last_synced_at" => "2026-05-30T15:42:18.000000Z"
    ]
    11 => array:5 [
      "bm_id" => "1436205884290675"
      "parent_bm_id" => "1310584400254306"
      "name" => "BM via 133A"
      "hidden_at" => null
      "last_synced_at" => "2026-06-02T09:00:36.000000Z"
    ]
    12 => array:5 [
      "bm_id" => "735519379107912"
      "parent_bm_id" => "1310584400254306"
      "name" => "Dong Hai 123"
      "hidden_at" => null
      "last_synced_at" => "2026-06-02T09:00:36.000000Z"
    ]
    13 => array:5 [
      "bm_id" => "1489040835637979"
      "parent_bm_id" => "1310584400254306"
      "name" => "HT 93"
      "hidden_at" => null
      "last_synced_at" => "2026-05-30T15:42:11.000000Z"
    ]
    14 => array:5 [
      "bm_id" => "682598489571950"
      "parent_bm_id" => "1310584400254306"
      "name" => "Bm2500 Pacific Media Usd"
      "hidden_at" => null
      "last_synced_at" => "2026-05-30T15:42:13.000000Z"
    ]
  ]
] // vendor/psy/psysh/src/ExecutionClosure.php(41) : eval()'d code:2
root@2511240624183887hovanvinh:/var/www/html/nhm-ads-agency-adsviet# php artisan tinker --execute '$bm="1310584400254306"; $rows=DB::table("meta_account_business_manager_accesses")->where("source_bm_id",$bm)->get(["source_bm_id","owner_bm_id","account_id","last_synced_at"]); dump(["access_count"=>$rows->count(),"access_sample"=>$rows->take(20)->toArray()]);'
array:2 [
  "access_count" => 11
  "access_sample" => array:11 [
    0 => {#5665
      +"source_bm_id": "1310584400254306"
      +"owner_bm_id": "1436205884290675"
      +"account_id": "act_691510320667452"
      +"last_synced_at": "2026-06-02 09:00:36"
    }
    1 => {#5660
      +"source_bm_id": "1310584400254306"
      +"owner_bm_id": "735519379107912"
      +"account_id": "act_809862148745885"
      +"last_synced_at": "2026-06-02 09:00:36"
    }
    2 => {#5663
      +"source_bm_id": "1310584400254306"
      +"owner_bm_id": "9793342250694664"
      +"account_id": "act_844260098388081"
      +"last_synced_at": "2026-06-02 09:00:36"
    }
    3 => {#5661
      +"source_bm_id": "1310584400254306"
      +"owner_bm_id": "214925643402466"
      +"account_id": "act_367460914938839"
      +"last_synced_at": "2026-05-25 16:01:05"
    }
    4 => {#5659
      +"source_bm_id": "1310584400254306"
      +"owner_bm_id": "214925643402466"
      +"account_id": "act_1032728120647798"
      +"last_synced_at": "2026-05-25 16:01:05"
    }
    5 => {#5658
      +"source_bm_id": "1310584400254306"
      +"owner_bm_id": "214925643402466"
      +"account_id": "act_349118277112451"
      +"last_synced_at": "2026-05-25 16:01:05"
    }
    6 => {#5657
      +"source_bm_id": "1310584400254306"
      +"owner_bm_id": "214925643402466"
      +"account_id": "act_978273446145835"
      +"last_synced_at": "2026-05-25 16:01:05"
    }
    7 => {#5656
      +"source_bm_id": "1310584400254306"
      +"owner_bm_id": "214925643402466"
      +"account_id": "act_443926294085734"
      +"last_synced_at": "2026-05-25 16:01:05"
    }
    8 => {#5655
      +"source_bm_id": "1310584400254306"
      +"owner_bm_id": "214925643402466"
      +"account_id": "act_478745820545731"
      +"last_synced_at": "2026-05-25 16:01:06"
    }
    9 => {#5654
      +"source_bm_id": "1310584400254306"
      +"owner_bm_id": "214925643402466"
      +"account_id": "act_1406397973124924"
      +"last_synced_at": "2026-05-25 16:01:06"
    }
    10 => {#5653
      +"source_bm_id": "1310584400254306"
      +"owner_bm_id": "214925643402466"
      +"account_id": "act_732035308183982"
      +"last_synced_at": "2026-05-25 16:01:06"
    }
  ]
] // vendor/psy/psysh/src/ExecutionClosure.php(41) : eval()'d code:3
root@2511240624183887hovanvinh:/var/www/html/nhm-ads-agency-adsviet# php artisan tinker --execute '$bm="1310584400254306"; $accIds=DB::table("meta_account_business_manager_accesses")->where("source_bm_id",$bm)->pluck("account_id")->map(fn($v)=>(string)$v)->unique()->values(); $accounts=DB::table("meta_accounts")->whereIn("account_id",$accIds)->get(["id","service_user_id","business_manager_id","account_id","account_name","account_status","amount_spent","balance","currency","last_synced_at"]); dump(["access_account_ids"=>$accIds->toArray(),"meta_accounts_count"=>$accounts->count(),"meta_accounts"=>$accounts->toArray()]);'
array:3 [
  "access_account_ids" => array:11 [
    0 => "act_691510320667452"
    1 => "act_809862148745885"
    2 => "act_844260098388081"
    3 => "act_367460914938839"
    4 => "act_1032728120647798"
    5 => "act_349118277112451"
    6 => "act_978273446145835"
    7 => "act_443926294085734"
    8 => "act_478745820545731"
    9 => "act_1406397973124924"
    10 => "act_732035308183982"
  ]
  "meta_accounts_count" => 11
  "meta_accounts" => array:11 [
    0 => {#5758
      +"id": 82401756516975796
      +"service_user_id": 83547811731211842
      +"business_manager_id": "214925643402466"
      +"account_id": "act_732035308183982"
      +"account_name": "SC27-(GMT+7)-Adpro-010"
      +"account_status": 1
      +"amount_spent": "293126"
      +"balance": "23894"
      +"currency": "VND"
      +"last_synced_at": "2026-05-25 16:01:06"
    }
    1 => {#5761
      +"id": 82401756397438726
      +"service_user_id": null
      +"business_manager_id": "9793342250694664"
      +"account_id": "act_844260098388081"
      +"account_name": "SC27-(GMT-8)- 2 -DH"
      +"account_status": 2
      +"amount_spent": "0"
      +"balance": "0"
      +"currency": "USD"
      +"last_synced_at": "2026-06-02 09:00:36"
    }
    2 => {#5757
      +"id": 82401756528510473
      +"service_user_id": null
      +"business_manager_id": "1436205884290675"
      +"account_id": "act_691510320667452"
      +"account_name": "001-HYHD-SC27-(GMT-8)-NA-185"
      +"account_status": 3
      +"amount_spent": "113187"
      +"balance": "2121"
      +"currency": "USD"
      +"last_synced_at": "2026-06-02 09:00:36"
    }
    3 => {#5759
      +"id": 82401756540044348
      +"service_user_id": null
      +"business_manager_id": "735519379107912"
      +"account_id": "act_809862148745885"
      +"account_name": "SC27-(GMT-8)-DH-01"
      +"account_status": 3
      +"amount_spent": "0"
      +"balance": "1832"
      +"currency": "USD"
      +"last_synced_at": "2026-06-02 09:00:36"
    }
    4 => {#5763
      +"id": 82401756432041045
      +"service_user_id": 83547811731211842
      +"business_manager_id": "214925643402466"
      +"account_id": "act_349118277112451"
      +"account_name": "SC27-(GMT+7)-Adpro-12"
      +"account_status": 1
      +"amount_spent": "53281"
      +"balance": "5882"
      +"currency": "VND"
      +"last_synced_at": "2026-05-25 16:01:05"
    }
    5 => {#5760
      +"id": 82401756463498684
      +"service_user_id": 83547811731211842
      +"business_manager_id": "214925643402466"
      +"account_id": "act_978273446145835"
      +"account_name": "SC27-(GMT+7)-Adpro-05"
      +"account_status": 1
      +"amount_spent": "179604396"
      +"balance": "9845372"
      +"currency": "VND"
      +"last_synced_at": "2026-05-25 16:01:05"
    }
    6 => {#5765
      +"id": 82401756485518560
      +"service_user_id": 83547811731211842
      +"business_manager_id": "214925643402466"
      +"account_id": "act_443926294085734"
      +"account_name": "SC27-(GMT+7)-Adpro-06"
      +"account_status": 1
      +"amount_spent": "0"
      +"balance": "24952"
      +"currency": "VND"
      +"last_synced_at": "2026-05-25 16:01:05"
    }
    7 => {#5764
      +"id": 82401756412118394
      +"service_user_id": 83547811731211842
      +"business_manager_id": "214925643402466"
      +"account_id": "act_367460914938839"
      +"account_name": "SC27-(GMT+7)-Adpro-01"
      +"account_status": 1
      +"amount_spent": "17811"
      +"balance": "76658"
      +"currency": "VND"
      +"last_synced_at": "2026-05-25 16:01:05"
    }
    8 => {#1565
      +"id": 82401756497053053
      +"service_user_id": 83547811731211842
      +"business_manager_id": "214925643402466"
      +"account_id": "act_478745820545731"
      +"account_name": "SC27-(GMT+7)-Adpro-13"
      +"account_status": 2
      +"amount_spent": "14221"
      +"balance": "14221"
      +"currency": "VND"
      +"last_synced_at": "2026-05-25 16:01:06"
    }
    9 => {#5752
      +"id": 82401756508587695
      +"service_user_id": 83547811731211842
      +"business_manager_id": "214925643402466"
      +"account_id": "act_1406397973124924"
      +"account_name": "SC27-(GMT+7)-Adpro-07"
      +"account_status": 1
      +"amount_spent": "0"
      +"balance": "0"
      +"currency": "VND"
      +"last_synced_at": "2026-05-25 16:01:06"
    }
    10 => {#5751
      +"id": 82401756422604382
      +"service_user_id": 83547811731211842
      +"business_manager_id": "214925643402466"
      +"account_id": "act_1032728120647798"
      +"account_name": "SC27-(GMT+7)-Adpro-03"
      +"account_status": 1
      +"amount_spent": "0"
      +"balance": "943"
      +"currency": "VND"
      +"last_synced_at": "2026-05-25 16:01:05"
    }
  ]
] // vendor/psy/psysh/src/ExecutionClosure.php(41) : eval()'d code:4
root@2511240624183887hovanvinh:/var/www/html/nhm-ads-agency-adsviet# php artisan tinker --execute '$bm="1310584400254306"; $day="2026-06-02"; $accIds=DB::table("meta_account_business_manager_accesses")->where("source_bm_id",$bm)->pluck("account_id")->map(fn($v)=>(string)$v)->unique()->values(); $accounts=DB::table("meta_accounts")->whereIn("account_id",$accIds)->get(["id","account_id","account_name"]); $metaIds=$accounts->pluck("id"); $summary=DB::table("meta_ads_account_insights")->whereIn("meta_account_id",$metaIds)->where("date",$day)->selectRaw("count(*) as rows, COALESCE(SUM(CAST(spend AS DECIMAL(15,2))),0) as spend, COALESCE(SUM(CAST(reach AS DECIMAL(15,2))),0) as reach, MAX(last_synced_at) as last_synced_at")->first(); $details=DB::table("meta_ads_account_insights")->whereIn("meta_account_id",$metaIds)->where("date",$day)->get(["meta_account_id","date","spend","reach","last_synced_at"]); dump(["accounts"=>$accounts->toArray(),"insight_summary"=>$summary,"insight_details"=>$details->toArray()]);'
array:3 [
  "accounts" => array:11 [
    0 => {#5880
      +"id": 82401756516975796
      +"account_id": "act_732035308183982"
      +"account_name": "SC27-(GMT+7)-Adpro-010"
    }
    1 => {#5886
      +"id": 82401756397438726
      +"account_id": "act_844260098388081"
      +"account_name": "SC27-(GMT-8)- 2 -DH"
    }
    2 => {#5883
      +"id": 82401756528510473
      +"account_id": "act_691510320667452"
      +"account_name": "001-HYHD-SC27-(GMT-8)-NA-185"
    }
    3 => {#5884
      +"id": 82401756540044348
      +"account_id": "act_809862148745885"
      +"account_name": "SC27-(GMT-8)-DH-01"
    }
    4 => {#5888
      +"id": 82401756432041045
      +"account_id": "act_349118277112451"
      +"account_name": "SC27-(GMT+7)-Adpro-12"
    }
    5 => {#5885
      +"id": 82401756463498684
      +"account_id": "act_978273446145835"
      +"account_name": "SC27-(GMT+7)-Adpro-05"
    }
    6 => {#5890
      +"id": 82401756485518560
      +"account_id": "act_443926294085734"
      +"account_name": "SC27-(GMT+7)-Adpro-06"
    }
    7 => {#5889
      +"id": 82401756412118394
      +"account_id": "act_367460914938839"
      +"account_name": "SC27-(GMT+7)-Adpro-01"
    }
    8 => {#1565
      +"id": 82401756497053053
      +"account_id": "act_478745820545731"
      +"account_name": "SC27-(GMT+7)-Adpro-13"
    }
    9 => {#5877
      +"id": 82401756508587695
      +"account_id": "act_1406397973124924"
      +"account_name": "SC27-(GMT+7)-Adpro-07"
    }
    10 => {#5876
      +"id": 82401756422604382
      +"account_id": "act_1032728120647798"
      +"account_name": "SC27-(GMT+7)-Adpro-03"
    }
  ]
  "insight_summary" => {#5873
    +"rows": 0
    +"spend": "0"
    +"reach": "0"
    +"last_synced_at": null
  }
  "insight_details" => []
] // vendor/psy/psysh/src/ExecutionClosure.php(41) : eval()'d code:8
root@2511240624183887hovanvinh:/var/www/html/nhm-ads-agency-adsviet# php artisan tinker --execute 'dump(App\Models\User::query()->where("role",1)->get(["id","name","username","role"])->toArray());'
array:1 [
  0 => array:4 [
    "id" => "67440005154342663"
    "name" => "Admin User"
    "username" => "admin@admin.vn"
    "role" => 1
  ]
] // vendor/psy/psysh/src/ExecutionClosure.php(41) : eval()'d code:1
root@2511240624183887hovanvinh:/var/www/html/nhm-ads-agency-adsviet# 

root@2511240624183887hovanvinh:/var/www/html/nhm-ads-agency-adsviet# php artisan tinker --execute 'session(["active_meta_setting_id"=>"67447573506426862"]); Auth::loginUsingId("67440005154342663"); $dto=new App\Core\QueryListDTO(10,1,["platform"=>2,"start_date"=>"2026-06-02","end_date"=>"2026-06-02","child_manager_id"=>"1310584400254306","view"=>"account"],"created_at","desc"); $result=app(App\Service\BusinessManagerService::class)->getListBusinessManagers($dto); $data=$result->getData(); $p=$data["paginator"]??null; dump(["success"=>$result->isSuccess(),"total"=>$p?->total(),"stats"=>$data["stats"]??null,"totals"=>$data["totals"]??null,"rows"=>$p?collect($p->items())->map(fn($r)=>["account_id"=>$r["account_id"]??null,"account_name"=>$r["account_name"]??null,"bm_ids"=>$r["bm_ids"]??[],"scope_bm_ids"=>$r["scope_bm_ids"]??[],"total_spend"=>$r["total_spend"]??null,"last_synced_at"=>$r["last_synced_at"]??null])->toArray():null]);'
array:5 [
  "success" => true
  "total" => 0
  "stats" => array:4 [
    "total_accounts" => 0
    "active_accounts" => 0
    "disabled_accounts" => 0
    "by_platform" => array:2 [
      2 => array:3 [
        "total_accounts" => 0
        "active_accounts" => 0
        "disabled_accounts" => 0
      ]
      1 => array:3 [
        "total_accounts" => 0
        "active_accounts" => 0
        "disabled_accounts" => 0
      ]
    ]
  ]
  "totals" => array:5 [
    "total_spend" => 0
    "total_reach" => 0
    "currency" => "USD"
    "totals_by_currency" => []
    "last_synced_at" => "2026-06-02T09:00:36+00:00"
  ]
  "rows" => []
] // vendor/psy/psysh/src/ExecutionClosure.php(41) : eval()'d code:7
root@2511240624183887hovanvinh:/var/www/html/nhm-ads-agency-adsviet# php artisan tinker --execute '$bm="1310584400254306"; $accIds=DB::table("meta_account_business_manager_accesses")->where("source_bm_id",$bm)->pluck("account_id")->map(fn($v)=>(string)$v)->unique()->values(); $accounts=DB::table("meta_accounts")->whereIn("account_id",$accIds)->get(["id","account_id","account_name"]); $metaIds=$accounts->pluck("id"); $rows=DB::table("meta_ads_account_insights")->whereIn("meta_account_id",$metaIds)->selectRaw("date, count(*) as rows, COALESCE(SUM(CAST(spend AS DECIMAL(15,2))),0) as spend, MAX(last_synced_at) as last_synced_at")->groupBy("date")->orderByDesc("date")->limit(10)->get(); dump($rows->toArray());'
array:10 [
  0 => {#5774
    +"date": "2026-06-01"
    +"rows": 2
    +"spend": "206596.00"
    +"last_synced_at": "2026-06-02 09:00:54"
  }
  1 => {#5780
    +"date": "2026-05-31"
    +"rows": 1
    +"spend": "0.00"
    +"last_synced_at": "2026-06-02 09:00:54"
  }
  2 => {#5776
    +"date": "2026-05-30"
    +"rows": 2
    +"spend": "8399438.00"
    +"last_synced_at": "2026-06-02 09:00:54"
  }
  3 => {#5775
    +"date": "2026-05-29"
    +"rows": 2
    +"spend": "10276083.00"
    +"last_synced_at": "2026-06-02 09:00:54"
  }
  4 => {#5773
    +"date": "2026-05-28"
    +"rows": 3
    +"spend": "38252676.00"
    +"last_synced_at": "2026-06-02 09:00:54"
  }
  5 => {#5772
    +"date": "2026-05-27"
    +"rows": 3
    +"spend": "19156254.00"
    +"last_synced_at": "2026-06-02 09:00:55"
  }
  6 => {#5771
    +"date": "2026-05-26"
    +"rows": 3
    +"spend": "17369277.00"
    +"last_synced_at": "2026-06-02 09:00:55"
  }
  7 => {#5770
    +"date": "2026-05-25"
    +"rows": 3
    +"spend": "10084035.00"
    +"last_synced_at": "2026-06-02 09:00:55"
  }
  8 => {#5769
    +"date": "2026-05-24"
    +"rows": 6
    +"spend": "63594.00"
    +"last_synced_at": "2026-06-02 09:00:55"
  }
  9 => {#5768
    +"date": "2026-05-23"
    +"rows": 4
    +"spend": "10938300.00"
    +"last_synced_at": "2026-06-02 09:00:55"
  }
] // vendor/psy/psysh/src/ExecutionClosure.php(41) : eval()'d code:6
root@2511240624183887hovanvinh:/var/www/html/nhm-ads-agency-adsviet# grep -R "MetaService\|BusinessManagerService\|1310584400254306" -n storage/logs/errors storage/logs/laravel.log | tail -n 160
storage/logs/errors/error-2026-06-02.log:2615:[2026-06-02 09:00:02] local.ERROR: MetaService@syncBusinessManagers: cannot fetch parent BM info: No data found. Please try again later.  
storage/logs/errors/error-2026-06-02.log:2619:[2026-06-02 09:00:02] local.ERROR: MetaService@syncBusinessAssetGroups error: No data found. Please try again later.  
storage/logs/laravel.log:3058:#8 /var/www/html/nhm-ads-agency-adsviet/app/Service/BusinessManagerService.php(99): Illuminate\\Database\\Eloquent\\Builder->get()
storage/logs/laravel.log:3059:#9 /var/www/html/nhm-ads-agency-adsviet/app/Http/Controllers/BusinessManagerController.php(88): App\\Service\\BusinessManagerService->getChildManagersForFilter()
storage/logs/laravel.log:3144:#10 /var/www/html/nhm-ads-agency-adsviet/app/Service/BusinessManagerService.php(99): Illuminate\\Database\\Eloquent\\Builder->get()
storage/logs/laravel.log:3145:#11 /var/www/html/nhm-ads-agency-adsviet/app/Http/Controllers/BusinessManagerController.php(88): App\\Service\\BusinessManagerService->getChildManagersForFilter()
storage/logs/laravel.log:3230:#8 /var/www/html/nhm-ads-agency-adsviet/app/Service/BusinessManagerService.php(99): Illuminate\\Database\\Eloquent\\Builder->get()
storage/logs/laravel.log:3231:#9 /var/www/html/nhm-ads-agency-adsviet/app/Http/Controllers/BusinessManagerController.php(88): App\\Service\\BusinessManagerService->getChildManagersForFilter()
storage/logs/laravel.log:3316:#10 /var/www/html/nhm-ads-agency-adsviet/app/Service/BusinessManagerService.php(99): Illuminate\\Database\\Eloquent\\Builder->get()
storage/logs/laravel.log:3317:#11 /var/www/html/nhm-ads-agency-adsviet/app/Http/Controllers/BusinessManagerController.php(88): App\\Service\\BusinessManagerService->getChildManagersForFilter()
storage/logs/laravel.log:3402:#8 /var/www/html/nhm-ads-agency-adsviet/app/Service/BusinessManagerService.php(99): Illuminate\\Database\\Eloquent\\Builder->get()
storage/logs/laravel.log:3403:#9 /var/www/html/nhm-ads-agency-adsviet/app/Http/Controllers/BusinessManagerController.php(88): App\\Service\\BusinessManagerService->getChildManagersForFilter()
storage/logs/laravel.log:3488:#10 /var/www/html/nhm-ads-agency-adsviet/app/Service/BusinessManagerService.php(99): Illuminate\\Database\\Eloquent\\Builder->get()
storage/logs/laravel.log:3489:#11 /var/www/html/nhm-ads-agency-adsviet/app/Http/Controllers/BusinessManagerController.php(88): App\\Service\\BusinessManagerService->getChildManagersForFilter()
root@2511240624183887hovanvinh:/var/www/html/nhm-ads-agency-adsviet# 
