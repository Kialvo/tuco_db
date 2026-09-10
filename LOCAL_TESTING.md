# Testing the payments feature from the interface

Everything on `feature/order-timers`, before the PR.

Almost all of it is clickable — the fake checkout lets you buy tokens through the
UI, and the CRM has a screen for publication status, which is what settles them.
There is **one** thing with no screen (backdating a deadline clock); it's marked.

---

## Before anything: check where you are pointed

Your `.env` points at the **live database** by default, and this walkthrough
creates users and orders.

```bash
php artisan tinker --execute="echo config('database.connections.'.config('database.default').'.host');"
```

Anything containing `ondigitalocean.com` → **stop**. You want `127.0.0.1`.

---

## Setup (one-time, ~15 minutes)

### 1. Local database

XAMPP already has MariaDB — start **MySQL** in the Control Panel, then:

```bash
# Structure of everything — zero rows, so no customer data at all
/c/xampp/mysql/bin/mysqldump --no-data --single-transaction \
  -h <prod-host> -P 25060 -u <user> -p --ssl-mode=REQUIRED defaultdb > schema.sql

# Plus the catalogue tables WITH data — these are domains and reference lists,
# not customer records, and without them there is nothing to put in a cart
/c/xampp/mysql/bin/mysqldump --single-transaction \
  -h <prod-host> -P 25060 -u <user> -p --ssl-mode=REQUIRED defaultdb \
  websites countries currencies languages > catalogue.sql

/c/xampp/mysql/bin/mysql -u root -e "CREATE DATABASE tuco_local CHARACTER SET utf8mb4;"
/c/xampp/mysql/bin/mysql -u root tuco_local < schema.sql
/c/xampp/mysql/bin/mysql -u root tuco_local < catalogue.sql
```

### 2. Point `.env` at it

Comment the DigitalOcean host out rather than deleting it:

```
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tuco_local
DB_USERNAME=root
DB_PASSWORD=

PAYMENTS_DRIVER=fake
TOKENS_SPENDING_ENABLED=true
MARKETPLACE_AUTO_ADVANCE=true
MAIL_MAILER=log
```

`PAYMENTS_DRIVER=fake` gives you a checkout page with a Pay button and no Stripe.
`MAIL_MAILER=log` writes emails into `storage/logs/laravel.log` instead of sending.

### 3. Migrations, then run it

```bash
php artisan config:clear
php artisan migrate --path=database/migrations/2026_09_10_000001_add_token_holds_to_order_items_table.php
php artisan migrate --path=database/migrations/2026_09_10_000002_add_approval_reminder_to_order_items_table.php
php artisan migrate --path=database/migrations/2026_09_10_000003_create_marketplace_teams_tables.php
php artisan migrate --path=database/migrations/2026_09_10_000004_move_token_accounts_to_teams.php
composer dev
```

*(That's also a rehearsal of the production sequence — if `000004` misbehaves, you've
found it on a throwaway database.)*

### 4. Three accounts, through the interface

Log in as an existing admin → **Admin → Users → Add User**. Create:

| Name | Email | Role |
|---|---|---|
| Test Guest | `guest@test.local` | guest |
| Colleague | `colleague@test.local` | guest |

Set a password you'll remember, and make sure they're verified (the Add User form
marks them verified automatically).

---

# The walkthrough — all in the browser

## 1. Buying tokens

Log in as **guest@test.local** → **Tokens** in the sidebar.

- [ ] Balance reads **0**
- [ ] An amber **"Test mode — no real payment"** pill is showing
- [ ] There's a **Team** button in the header

Click **Buy** on the Standard package.

- [ ] You land on a plain checkout page (this is the fake gateway, not Stripe)
- [ ] Click **Pay**
- [ ] Back on Tokens: balance is **515** (500 + 15 bonus)
- [ ] The statement at the bottom shows **two separate rows** — "Token purchase" and "Bonus tokens"

Buy the Starter package too, so you have plenty. Balance should be **765**.

## 2. Ordering — tokens get held, not spent

Go to **Domains**. Add two or three domains to the cart (note their prices), open the cart.

- [ ] The breakdown shows **Your balance**, **This order**, **Balance after order**
- [ ] Click **Submit**

Back on **Tokens**:

- [ ] Balance has dropped by the order total
- [ ] A new line reads **"N on hold for orders in progress"**
- [ ] The statement shows a **hold** row

That's the whole point: the money is committed but not yet earned.

## 3. Not enough tokens

Add enough domains to exceed your balance, and open the cart.

- [ ] The green breakdown is replaced by an amber **"Not enough tokens for this order"**
- [ ] It shows current balance, minimum required, and exactly what's **missing**
- [ ] A **Buy tokens** button appears
- [ ] The **Submit** button is disabled-looking and does nothing

**One check that isn't clickable but matters** — prove the server enforces this, not
just the browser. Open devtools → Console, paste:

```js
fetch('/orders/submit', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
    'Accept': 'application/json',
  },
  body: '{}'
}).then(r => r.json().then(d => console.log(r.status, d)));
```

- [ ] Logs **422** with `missing`, `balance`, `required` — bypassing the UI does not place the order

Empty the cart back down before continuing.

## 4. Publishing settles the money

Log out, log in as **admin**. Go to **Campaigns**.

- [ ] There's a campaign named for the guest's order, service **LIAB Marketplace**

Open it. You'll see one publication per ordered domain, all at
**Waiting Blog Price Confirmation**.

Change the **first** publication's status to **Article Published**.
Change the **second** to **Publisher Refused**.

Now log back in as **guest@test.local** → **Tokens**:

- [ ] Balance went **up** by the refused domain's price — those tokens came back
- [ ] Balance did **not** move for the published one — it was already held, now it's earned
- [ ] "On hold" has dropped accordingly
- [ ] The statement now shows **release** and **spend** rows

## 5. Nothing settles twice

As admin, on that same campaign:

- [ ] Set the published one to **Article Published** again → guest's balance unchanged
- [ ] Set it to **Publisher Refused** → **balance still unchanged**, and the save succeeds

That second one is the important one: a mis-clicked status must not refund a
placement that already went live.

## 6. The shared wallet

As **guest@test.local** → **Tokens → Team**.

- [ ] You're listed as **Owner** of a team of one
- [ ] Enter `colleague@test.local` and click **Send invitation**
- [ ] It appears under **Pending invitations**

Open `storage/logs/laravel.log`, find the invitation email, copy the **join link**.

Log out → log in as **colleague@test.local** → paste the join link.

- [ ] "You have joined … You can now spend the shared balance"
- [ ] Their **Tokens** page shows the **same balance** as the owner's

**The security check.** Log out, log in as your **admin**, paste the *same* join link.

- [ ] **Refused** — "This invitation was sent to a different email address"

Back as the owner on **Team**:

- [ ] Click **Remove** on the colleague → confirmation warns they lose access
- [ ] Log in as the colleague → their balance is now **0** (their own empty team)
- [ ] Log in as the owner → balance **unchanged**

Also try, as the owner:

- [ ] **Make owner** on a member, then confirm you can no longer invite
- [ ] There's no Remove button next to the owner themselves

## 7. Admin adjustments

As **admin** → go to `/admin/tokens`.

- [ ] Teams listed with balances and member counts
- [ ] Search by the guest's email finds their team
- [ ] Add **250** tokens with a reason → balance rises, appears in **Last 20 adjustments** with your name
- [ ] Try removing **more than the balance** → refused, nothing moves
- [ ] Try a reason of 2 characters → refused
- [ ] Submit the same form twice quickly → only **one** adjustment appears

## 8. The deadlines

⚠️ **This is the one part with no screen.** The clocks are 3 and 5 working days, so
testing them means either waiting a week or backdating in the terminal.

```bash
php artisan tinker
```
```php
// Pick a publication still at Waiting Blog Price Confirmation
$pub = App\Models\Storage::where('status','waiting_blog_price_confirmation')->first();
DB::table('publication_status_events')->where('storage_id', $pub->id)
  ->update(['created_at' => now()->subDays(20)]);
```

```bash
php artisan marketplace:advance-overdue --dry-run
php artisan marketplace:advance-overdue
```

Then in the browser:

- [ ] As admin, that publication now reads **Publisher Disappeared**
- [ ] As the guest, those tokens are back in the balance

For the client-approval clock, set a publication to **Waiting Client Article
Approval** in the CRM, backdate it the same way, then run the command **twice**:

- [ ] **First run reminds but does not publish** — check `storage/logs/laravel.log`
      for the reminder email naming the publish date
- [ ] **Second run** moves it to **Waiting Blog Publication**

That two-run behaviour is deliberate: nothing can be auto-published before the
client has been warned.

## 9. Health check

```bash
php artisan tokens:reconcile
```

- [ ] **"Ledger and balances agree. Nothing to report."**

If it says anything else after all the clicking above, something's wrong and I
want to see the output.

---

## When you're done

Put `.env` back:

```
DB_HOST=<the DigitalOcean host>
DB_DATABASE=defaultdb
```

And **delete these four lines entirely** — absent means off, and a line sitting
there is one someone can flip by accident:

```
PAYMENTS_DRIVER, TOKENS_SPENDING_ENABLED, MARKETPLACE_AUTO_ADVANCE, MAIL_MAILER
```

```bash
php artisan config:clear
php artisan tinker --execute="var_dump(config('tokens.spending_enabled'));"   # bool(false)
```

---

## Before the PR

- [ ] `php artisan test --filter="Tokens|WorkingDays"` → **181 passed**
- [ ] `./vendor/bin/pint --test` → clean
- [ ] `.env` back on production, all four flags gone
- [ ] The ~40 untracked `*_SUPERPROMPT.md` and `.xlsx` files in `git status` predate
      this work — leave them alone
