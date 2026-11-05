# POS System: setup and changes

## Setting it up

1. Copy the `POS-PHP` folder into your web server (for XAMPP: `C:\xampp\htdocs\`).
2. Database:
   - **New install:** in phpMyAdmin create a database called `posystem` and import `DATABASE FILE/posystem.sql`.
   - **Already using the old version?** Back up your database, then run `DATABASE FILE/upgrade.sql` once on it (phpMyAdmin > your database > SQL tab). Your products, customers, sales and users are kept.
3. If your MySQL user or password is not `root` with no password, edit `config/config.php`.
4. Open the site and log in (default: `admin` / `codeastro`). **Change this password** under User Management.
5. Go to **Settings** (gear icon in the menu, admin only) and enter your business name, address, phone, KRA PIN, logo, currency, VAT rate, receipt message and M-Pesa till.

Works on PHP 7.4 to 8.3 (tested on PHP 8.3 with MariaDB 10.11). PHP 5.6 is no longer supported.

## What changed

**Settings page (new):** everything about your business is set in the app, no code editing needed:
business name, address, phone, email, KRA PIN, logo, time zone, currency symbol, VAT name and rate,
whether prices already include VAT, receipt size (till roll or A4), receipt footer, M-Pesa till number,
and the thermal printer.

**Receipts:**
- Use your business details, logo and footer instead of the template's fake address.
- Choose a till-roll ticket (80mm) or A4 invoice in Settings. Both show VAT and how the customer paid.
- Fixed a bug where the receipt total showed the last item's price instead of the sale total.
- Optional automatic printing to an ESC/POS thermal printer (USB shared on Windows, or network IP), with optional cash drawer opening.
- Receipts can only be opened by logged-in users.

**Currency and VAT:**
- Currency comes from Settings everywhere: sales, products, dashboard, reports, receipts, Excel export.
- VAT rate is fixed in Settings (default 16%); cashiers no longer type it per sale.
- Supports VAT-inclusive prices (default, as usual in Kenya) or VAT added on top.
- Tax is now stored with cents (it was being cut to whole numbers).

**M-Pesa:**
- New "M-Pesa" payment method. The cashier must enter the M-Pesa transaction code; it is saved with the sale and shown in the sales list, receipts and Excel export.
- Card transaction codes can now contain letters.

**Under the hood:**
- Runs on modern PHP 8: updated the PDF library (TCPDF 6.11) and printer library (escpos-php 4.0), fixed crashes on the dashboard and when adding users or products without a photo.
- Database login moved to `config/config.php`.
- Passwords are stored with PHP's secure `password_hash`. Old passwords still work and are upgraded automatically the next time each user logs in. Passwords may now contain symbols.
- Editing a user no longer trusts a password hash sent by the browser, and user data sent to the browser no longer includes the password hash.
- Background (ajax) pages, receipts and the Excel report now require login.
- Deleting products/users can only delete image files inside `views/img`.
- Fixed: editing a sale changed its date; editing a sale's products didn't restore stock correctly; charts failed to load on Linux servers (wrong file name case); hard-coded Bogota time zone.
