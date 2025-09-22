# Files changed between commits

Range: `ec241364ebb09847ebd00b8cb2d173b5c6cf841b` → `8ca0448971ce5fdbd4f6d8a2944bea8c460c8758`

-   app

    -   Console
        -   Commands
            -   app/Console/Commands/StressTestVariants.php
            -   app/Console/Commands/TestFrontendSync.php
            -   app/Console/Commands/TestPrintServiceFlow.php
            -   app/Console/Commands/TestVariantAttributeFormatting.php
            -   app/Console/Commands/TestVariantCreation.php
    -   Http
        -   Controllers
            -   Admin
                -   app/Http/Controllers/Admin/OrderController.php
                -   app/Http/Controllers/Admin/PaperTypeController.php
                -   app/Http/Controllers/Admin/PrintTypeController.php
                -   app/Http/Controllers/Admin/ProductController.php
            -   Frontend
                -   app/Http/Controllers/Frontend/HomepageController.php
                -   app/Http/Controllers/Frontend/OrderController.php
            -   app/Http/Controllers/PrintServiceController.php
    -   Models
        -   app/Models/PaperType.php
        -   app/Models/PrintType.php
        -   app/Models/ProductVariant.php
    -   Services
        -   app/Services/PrintService.php
        -   app/Services/ProductVariantService.php
        -   app/Services/StockManagementService.php

-   database

    -   migrations
        -   database/migrations/2025_09_21_160130_expand_paper_size_enum_values.php
        -   database/migrations/2025_09_21_161120_convert_enum_to_string_for_dynamic_paper_types.php
        -   database/migrations/2025_09_21_161303_create_paper_types_table.php
        -   database/migrations/2025_09_21_161321_create_print_types_table.php
        -   database/migrations/2025_09_21_162056_update_print_orders_enum_to_string.php

-   resources

    -   views
        -   admin
            -   orders
                -   resources/views/admin/orders/index.blade.php
                -   resources/views/admin/orders/invoices.blade.php
            -   print-service
                -   resources/views/admin/print-service/stock.blade.php
                -   resources/views/admin/print-service/stock-report.blade.php
            -   products
                -   resources/views/admin/products/edit.blade.php
                -   partials
                    -   resources/views/admin/products/partials/variant-modal-script.blade.php
        -   frontend
            -   orders
                -   resources/views/frontend/orders/checkout.blade.php
                -   resources/views/frontend/orders/received.blade.php
            -   shop
                -   resources/views/frontend/shop/index.blade.php
        -   print-service
            -   resources/views/print-service/index.blade.php

-   routes

    -   routes/web.php

-   repository root
    -   FLEXIBLE_VARIANT_SYSTEM_SUCCESS.md
    -   VARIANT_UPDATES_SUMMARY.md
    -   test_variant_ui_updated.html
