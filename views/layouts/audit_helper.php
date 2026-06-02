<?php
/**
 * Helper to display human-readable differences between old and new values in audit logs.
 */
function renderRevisionDetails(array $log): string {
    $old = $log['old_values'] ? json_decode($log['old_values'], true) : null;
    $new = $log['new_values'] ? json_decode($new_values = $log['new_values'], true) : null;
    $user = htmlspecialchars($log['responsible_user'] ?? 'System');
    $action = $log['action'];
    
    // Default actions
    if ($action === 'create') return "<strong>{$user}</strong> created the record";
    if ($action === 'delete') return "<strong>{$user}</strong> deleted the record";

    if (!$old && !$new) return "<strong>{$user}</strong> performed an action";
    
    // Ignore meta fields
    $ignore = ['updated_at', 'created_at', 'id', 'csrf_token', 'user_id', 'creator_id', 'type_id', 'network_id', 'block_id', 'task_id', 'responsible_user', 'action'];
    
    // Get all unique keys
    $keys = array_unique(array_merge(array_keys($old ?? []), array_keys($new ?? [])));
    $details = [];
    
    foreach ($keys as $key) {
        $lowKey = strtolower($key);
        if (in_array($lowKey, $ignore) || str_ends_with($lowKey, '_id')) continue;
        
        $oldVal = $old[$key] ?? null;
        $newVal = $new[$key] ?? null;
        
        // Normalize empty values
        $normOld = ($oldVal === null || $oldVal === '') ? null : $oldVal;
        $normNew = ($newVal === null || $newVal === '') ? null : $newVal;

        if ($normOld === $normNew) continue;
        
        // Format values for display
        $format = function($val) {
            if ($val === null || $val === '') return 'none';
            if (is_array($val)) return htmlspecialchars(implode(', ', $val));
            if (is_bool($val)) return $val ? 'true' : 'false';
            return htmlspecialchars((string)$val);
        };

        $label = ucwords(str_replace(['_', '[]'], [' ', ''], $key));
        if ($lowKey === 'status') $label = 'Status';

        $entry = "<strong>{$user}</strong> updated <span class=\"font-medium text-slate-700 dark:text-slate-300\">{$label}</span> from ";
        $entry .= '<span class="text-slate-400">' . $format($oldVal) . '</span> to ';
        $entry .= '<span class="text-emerald-500 font-bold">' . $format($newVal) . '</span>';
        $details[] = $entry;
    }
    
    if (empty($details)) {
        return "<strong>{$user}</strong> updated the record";
    }

    return implode('<br>', $details);
}
