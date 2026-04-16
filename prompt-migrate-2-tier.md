# Prompt — TheDay Migrate from 3-Tier to 2-Tier Pricing (Free & Premium)

You are refactoring the pricing and subscription system of TheDay from 3 tiers (Free, Silver, Gold) to 2 tiers (Free, Premium).

Context:
- TheDay is a premium, mobile-first digital wedding invitation SaaS for Indonesia.
- Previously the system had 3 subscription tiers: Free, Silver (Rp 99.000/30 days), Gold (Rp 199.000/30 days).
- The new structure simplifies to 2 tiers: Free and Premium.
- Premium replaces both Silver and Gold as a single paid tier.
- All existing Silver and Gold users must be migrated gracefully.
- All plan-gating logic across the codebase must be updated.
- No user should lose access to features they paid for.

---

## New Tier Structure

### Free
- Price: Rp 0
- Max 1 active invitation
- Max 5 gallery photos
- Free templates only
- Default music library only
- Watermark "Dibuat dengan TheDay" shown
- No analytics
- No custom slug
- No password protection

### Premium
- Price: Rp 149.000 / 30 days (determine exact price with product owner if needed)
- Unlimited active invitations
- Unlimited gallery photos
- All templates (free + premium)
- Upload custom music
- No watermark
- Custom URL slug
- Password protection
- Analytics dashboard
- Priority support
- All features that were previously in Silver and Gold combined

---

## Migration Rules for Existing Users

### Silver users → Premium
- Automatically upgraded to Premium plan
- Premium expiry = their current Silver expiry date
- No action required from user
- They gain Gold features they didn't have before (analytics, password protection, etc.)
- This is a bonus, not a problem

### Gold users → Premium
- Already mapped 1:1 to Premium
- Expiry date preserved
- No downgrade in features

### Free users
- Stay on Free
- No migration needed

### Migration must be:
- Run as a database migration or seeder script
- Idempotent (safe to run multiple times)
- Logged (who was migrated and when)
- Not break any active invitation or feature state

---

## Database Changes

### `plans` or `packages` table
If plans are stored in database:
- Soft delete or deactivate Silver and Gold records
- Add or update Premium plan record with correct features and price
- Do not hard delete Silver/Gold in case of audit needs

### `subscriptions` or `user_plans` table
- All active Silver subscriptions: update plan reference to Premium, preserve expiry
- All active Gold subscriptions: update plan reference to Premium, preserve expiry
- Historical expired subscriptions: leave as-is for audit trail, just update display label if needed

### Plan slug / key
Old:
- `free`
- `silver`
- `gold`

New:
- `free`
- `premium`

All code referencing `silver` or `gold` plan keys must be updated.

---

## Code Refactoring Required

### 1. Plan/feature gate checks
Search entire codebase for:
- `silver`
- `gold`
- `plan === 'silver'`
- `plan === 'gold'`
- `isPremium`
- `hasPlan('silver')`
- `hasPlan('gold')`

Replace all gating logic with simple 2-tier check:
```php
// Old
$user->plan === 'silver' || $user->plan === 'gold'

// New
$user->plan === 'premium'
```

Or use a helper method:
```php
$user->isPremium() // returns true if plan === 'premium'
$user->isFree()    // returns true if plan === 'free' or no active subscription
```

### 2. Feature flags / config
If feature flags are stored in config or plan definitions:
- Merge Silver and Gold feature sets into single Premium definition
- Remove Silver and Gold definitions

### 3. Middleware / policies
Update any middleware that checks plan tier:
- PlanMiddleware, or similar
- Invitation limit checks
- Photo limit checks
- Template access checks

### 4. Frontend plan checks
Update Vue components and Inertia page props that:
- Show/hide features based on plan
- Show upgrade prompts based on plan

Replace all 3-tier logic with simple isPremium boolean.

### 5. Pricing page / upgrade page
Update all UI references from "Silver" and "Gold" to "Premium".

### 6. Email templates
Update any email that mentions plan names (welcome email, payment confirmation, expiry reminder).

### 7. Admin panel
Update admin panel plan labels and management if applicable.

---

## Migration Script

Create a Laravel migration or console command:
`php artisan theday:migrate-plans`

Steps:
1. Get all active Silver subscriptions
2. Map each to Premium with same expiry
3. Get all active Gold subscriptions
4. Map each to Premium with same expiry
5. Log each migration with timestamp and old/new plan info
6. Deactivate Silver and Gold plan records in plans table
7. Report summary: X Silver migrated, Y Gold migrated

---

## Plan Helper on User Model

Add/update methods on User model:

```php
public function isPremium(): bool
{
    return $this->activeSubscription?->plan->slug === 'premium';
}

public function isFree(): bool
{
    return !$this->isPremium();
}

public function planSlug(): string
{
    return $this->isPremium() ? 'premium' : 'free';
}
```

---

## Feature Limits Config

Centralize all plan limits in a single config file `config/plans.php`:

```php
return [
    'free' => [
        'max_invitations'   => 1,
        'max_photos'        => 5,
        'premium_templates' => false,
        'custom_music'      => false,
        'watermark'         => true,
        'custom_slug'       => false,
        'password_protect'  => false,
        'analytics'         => false,
        'priority_support'  => false,
    ],
    'premium' => [
        'max_invitations'   => null,  // unlimited
        'max_photos'        => null,  // unlimited
        'premium_templates' => true,
        'custom_music'      => true,
        'watermark'         => false,
        'custom_slug'       => true,
        'password_protect'  => true,
        'analytics'         => true,
        'priority_support'  => true,
    ],
];
```

All feature gate checks must reference this config, not hardcoded plan names.

---

## Upgrade Prompt Updates

All upgrade prompts across the app that mention "Silver" or "Gold" must be updated to simply say "Premium":

Old:
- "Fitur ini tersedia di paket Silver dan Gold."
- "Upgrade ke Silver untuk akses template premium."

New:
- "Fitur ini tersedia di paket Premium."
- "Upgrade ke Premium untuk akses template premium."

---

## Edge Cases

### 1. User has both Silver and Gold (shouldn't happen, but handle it)
- Migrate to Premium using the later expiry date

### 2. Expired Silver or Gold subscriptions
- Leave historical data as-is
- Display as "Kadaluarsa" with plan name "Premium (eks Silver)" or just "Premium"

### 3. User in the middle of a payment flow for Silver or Gold
- If payment is still pending: auto-complete as Premium
- Update order/invoice to reference Premium plan
- Do not reject the payment

### 4. Admin reports showing Silver/Gold revenue
- Preserve historical transaction records
- Just update display labels in admin panel
- Never delete financial records

### 5. API consumers referencing plan names (if any public API exists)
- Keep backward-compatible aliases if needed

---

## Deliverables
Produce:
1. Database migration to add Premium plan, deactivate Silver and Gold
2. `php artisan theday:migrate-plans` console command
3. Updated User model with isPremium(), isFree(), planSlug() helpers
4. `config/plans.php` with centralized feature limits
5. Updated all feature gate checks across controllers, policies, middleware
6. Updated all Vue components referencing plan names
7. Updated upgrade prompts across all pages
8. Updated email templates with new plan names
9. Updated pricing/upgrade page references
10. Test: all Silver/Gold users have Premium access after migration

---

## Acceptance Criteria
Implementation is successful when:
1. Free users remain on Free with correct limits.
2. All active Silver/Gold users are on Premium with preserved expiry.
3. All feature gates use isPremium() instead of silver/gold checks.
4. No UI anywhere mentions "Silver" or "Gold" to users.
5. New payments can only purchase Free or Premium.
6. Config-driven plan limits are the single source of truth.
7. Migration is idempotent and logged.
