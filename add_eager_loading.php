<?php
$files = [
    'app/Filament/Resources/DeliveryOrderResource.php' => "->with(['warehouse', 'contact', 'items'])",
    'app/Filament/Resources/InvoiceResource.php' => "->with(['contact', 'deliveryOrder', 'items'])"
];

foreach ($files as $file => $with) {
    $content = file_get_contents($file);
    if (!str_contains($content, 'getEloquentQuery')) {
        $add = "
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()" . $with . "->withoutGlobalScopes([\Illuminate\Database\Eloquent\SoftDeletingScope::class]);
    }
}";
        $content = preg_replace('/\}$/', $add, $content);
        file_put_contents($file, $content);
    }
}
