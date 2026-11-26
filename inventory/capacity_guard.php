<?php

/**
 * Lock a location and reject incoming stock that would fill it to 90% or more.
 * Call only inside an active database transaction.
 */
function guard_location_capacity(PDO $pdo, int $locationId, int $incomingQuantity): array
{
    if ($locationId <= 0) {
        throw new RuntimeException('Select a storage location before adding stock.');
    }

    $locationQuery = $pdo->prepare('SELECT id, location_code, capacity, status FROM warehouse_locations WHERE id = ? FOR UPDATE');
    $locationQuery->execute([$locationId]);
    $location = $locationQuery->fetch(PDO::FETCH_ASSOC);
    if (!$location || $location['status'] !== 'Active') {
        throw new RuntimeException('The selected storage location is unavailable. Choose an active location.');
    }

    $stockQuery = $pdo->prepare("SELECT COALESCE(SUM(current_quantity), 0) FROM products WHERE location_id = ? AND status = 'Active'");
    $stockQuery->execute([$locationId]);
    $currentStock = (int) $stockQuery->fetchColumn();
    $projectedStock = $currentStock + max(0, $incomingQuantity);
    $capacity = (int) $location['capacity'];
    $warningLimit = $capacity * 0.9;

    if ($projectedStock >= $warningLimit) {
        $reason = $currentStock >= $warningLimit || $projectedStock > $capacity
            ? 'full or nearly full'
            : 'would become nearly full';
        throw new RuntimeException(sprintf(
            'Storage location %s is %s (%s / %s pcs). Adding %s pcs would bring it to %s pcs; the 90%% limit prevents this stock addition.',
            $location['location_code'],
            $reason,
            number_format($currentStock),
            number_format($capacity),
            number_format(max(0, $incomingQuantity)),
            number_format($projectedStock)
        ));
    }

    return ['location' => $location, 'current_stock' => $currentStock, 'projected_stock' => $projectedStock];
}
