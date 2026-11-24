Tickets4You Customer Dashboard — missing sidebar / unstyled cards hotfix

Extract app/layout.php and assets/style.css directly into C:\xampp\htdocs\T4Y, replacing those two files only. Keep all other files, config.php, database, and storage unchanged. Refresh customer dashboard at http://localhost/T4Y/index.php?page=dashboard with Ctrl+F5. app/layout.php includes a version query parameter for style.css to prevent stale browser caching. 

Verify CSS file includes .customer-workspace and .customer-sidebar. This addresses missing new CSS, not missing user permissions.
