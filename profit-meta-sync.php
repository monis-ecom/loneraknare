<?php

function profit_meta_default_api_version() {
    return 'v25.0';
}

function profit_meta_safe_api_version($version) {
    $version = trim((string) $version);
    return preg_match('/^v[0-9]+\.[0-9]+$/', $version) ? $version : profit_meta_default_api_version();
}

function profit_meta_float($value) {
    if (is_array($value)) {
        return 0.0;
    }
    $normalized = str_replace(array(' ', ','), array('', '.'), (string) $value);
    return is_numeric($normalized) ? (float) $normalized : 0.0;
}

function profit_meta_account_node($accountId) {
    $accountId = trim((string) $accountId);
    if (preg_match('/^act_[0-9]+$/', $accountId)) {
        return $accountId;
    }
    if (preg_match('/^[0-9]+$/', $accountId)) {
        return 'act_' . $accountId;
    }
    return '';
}

function profit_meta_sync_window($mode, $today = null) {
    $today = $today ? date('Y-m-d', strtotime($today)) : date('Y-m-d');
    $days = 1;
    if ($mode === 'nightly') {
        $days = 6;
    } elseif ($mode === 'weekly') {
        $days = 29;
    }
    return array(date('Y-m-d', strtotime($today . ' -' . $days . ' days')), $today);
}

function profit_meta_action_value($items, $actionTypes) {
    if (!is_array($items)) {
        return 0.0;
    }
    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }
        $type = (string) ($item['action_type'] ?? '');
        if (in_array($type, $actionTypes, true)) {
            return profit_meta_float($item['value'] ?? 0);
        }
    }
    return 0.0;
}

function profit_meta_purchase_action_types() {
    return array(
        'purchase',
        'omni_purchase',
        'offsite_conversion.fb_pixel_purchase',
        'onsite_conversion.purchase',
    );
}

function profit_meta_insights_fields() {
    return array(
        'date_start',
        'date_stop',
        'campaign_id',
        'campaign_name',
        'adset_id',
        'adset_name',
        'ad_id',
        'ad_name',
        'spend',
        'clicks',
        'impressions',
        'cpc',
        'cpm',
        'ctr',
        'actions',
        'action_values',
    );
}

function profit_meta_build_insights_url($accountId, $token, $from, $to, $apiVersion = '') {
    $accountNode = profit_meta_account_node($accountId);
    $version = profit_meta_safe_api_version($apiVersion);
    $params = array(
        'level' => 'ad',
        'time_increment' => 1,
        'limit' => 500,   // large page size so multi-week ranges need far fewer pages
        'fields' => implode(',', profit_meta_insights_fields()),
        'time_range' => json_encode(array(
            'since' => date('Y-m-d', strtotime($from)),
            'until' => date('Y-m-d', strtotime($to)),
        ), JSON_UNESCAPED_SLASHES),
        'access_token' => (string) $token,
    );

    return 'https://graph.facebook.com/' . rawurlencode($version) . '/' . rawurlencode($accountNode) . '/insights?' . http_build_query($params);
}

function profit_meta_normalize_insights($rows, $accountId) {
    $accountNode = profit_meta_account_node($accountId);
    $normalized = array();
    $purchaseTypes = profit_meta_purchase_action_types();

    if (!is_array($rows)) {
        return $normalized;
    }

    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $date = date('Y-m-d', strtotime($row['date_start'] ?? ''));
        if ($date === '1970-01-01') {
            continue;
        }
        $campaignId = trim((string) ($row['campaign_id'] ?? 'account_total'));
        if ($campaignId === '') {
            $campaignId = 'account_total';
        }
        $campaignName = trim((string) ($row['campaign_name'] ?? 'Account total'));
        if ($campaignName === '') {
            $campaignName = 'Account total';
        }
        $adsetId = trim((string) ($row['adset_id'] ?? ''));
        $adsetName = trim((string) ($row['adset_name'] ?? ''));
        $adId = trim((string) ($row['ad_id'] ?? ''));
        $adName = trim((string) ($row['ad_name'] ?? ''));
        if ($adId === '') {
            $adId = 'unknown_ad';
        }
        if ($adName === '') {
            $adName = 'Unknown ad';
        }

        $normalized[] = array(
            'insight_date' => $date,
            'account_id' => $accountNode,
            'campaign_id' => $campaignId,
            'campaign_name' => $campaignName,
            'adset_id' => $adsetId,
            'adset_name' => $adsetName,
            'ad_id' => $adId,
            'ad_name' => $adName,
            'spend' => profit_meta_float($row['spend'] ?? 0),
            'clicks' => max(0, (int) profit_meta_float($row['clicks'] ?? 0)),
            'impressions' => max(0, (int) profit_meta_float($row['impressions'] ?? 0)),
            'cpc' => profit_meta_float($row['cpc'] ?? 0),
            'cpm' => profit_meta_float($row['cpm'] ?? 0),
            'ctr' => profit_meta_float($row['ctr'] ?? 0),
            'purchases' => profit_meta_action_value($row['actions'] ?? array(), $purchaseTypes),
            'purchase_value' => profit_meta_action_value($row['action_values'] ?? array(), $purchaseTypes),
            'source_key' => 'meta:' . $accountNode . ':' . $adId . ':' . $date,
            'raw' => $row,
        );
    }

    return $normalized;
}
