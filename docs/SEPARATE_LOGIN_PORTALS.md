# Tickets4You · Separate Login Portals

The original `?page=login` now shows a role chooser. Each selection opens a dedicated page:

| Role | Page | Sign-in identifier | Access rule |
| --- | --- | --- | --- |
| Customer | `?page=login_customer` | Email | Account must have Customer role |
| Organiser | `?page=login_organiser` | Personal email | Account must have Organiser role (same person/account as Customer) |
| Staff | `?page=login_staff` | Admin-issued Staff ID | Account must have Staff role |
| Administrator | `?page=login_admin` | Admin email | Account must have Admin role |

* Only Customer/Organiser sign-in pages show **Forgot password?**, in blue next to Password; their form is on `?page=forgot`.
* Staff members use the Staff Help page; administrator-verified credential reset is performed by an existing administrator.
* The server checks role and identifier type, **not just the login form**. Wrong portal or identifier is rejected.
* Existing 2FA challenge remains enabled where configured, and checks the selected role again before completing the login.
* There is no database migration. All existing accounts and payment/booking records remain unchanged.
* The navbar's Sign in entry and old `?page=login` links continue to work, opening the chooser.
* This update does not imply that prototype authentication is production hardened. Use HTTPS and security review before any real deployment.

## Installation on Windows XAMPP
1. Back up your current folder `C:\xampp\htdocs\T4Y_Chatgpt\T4Y`.
2. Extract the **login upgrade ZIP** directly into that folder (not into another nested `T4Y`). Overwrite the listed files.
3. Keep `storage/`, `storage/app.key`, your `config.php`, and your MySQL database. No reinstall, schema import, or demo reseeding is needed.
4. Refresh `http://localhost/T4Y_Chatgpt/T4Y/index.php?page=login` with Ctrl+F5.
5. Verify Customer/Organiser Email, Staff ID, and Admin Email portals; try a mismatched portal to confirm it is rejected.
