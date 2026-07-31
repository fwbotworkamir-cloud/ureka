# Ureka Ads — brief for the next session

Written 1 Aug 2026. Read this **plus** `docs/ads-strategy.md` (the plan and its audit) before doing anything.
Memory to load first: `ads-playbook-cross-account`, `feedback-ads-check-vs-fix`, `ureka-client-overview`.

---

## 1. Standing rules (from other accounts — do not relearn these the expensive way)

- **"Check" ≠ "fix"** on a live, money-spending account. Audit, report, propose — then wait for a go-ahead. This is stricter than the normal permission bar.
- **Never auto-accept Google's bulk recommendations.** On Sky Sigma this deleted 33 keywords in one click (2 Jun 2026), recovered only via Change History undo.
- **Never enter billing/card details.** That is Amir's step, always. New accounts stay trapped in the onboarding wizard until he does it — expect this here.
- **No PMax / AI Max / broad match / Target CPA** until conversions have flowed for ≥4 weeks. Start Maximise clicks, exact + phrase only.
- **Verify the account is the right one.** UMJ and Al Marwah each had two accounts with near-identical names; one was live, one was dead. Confirm before touching.
- **Operate via** `fwbotworkamir@gmail.com` → **Fw** MCC (945-133-2343). Google's authuser URLs are sticky — switch via the profile avatar, not the URL.

## 2. Ureka account facts

- Google Ads **798-872-3174** ("Ureka Edu corp"). **Billing not active** → nothing can run yet.
- GA4 **G-0BJ1XCV8LZ** is live site-wide; `generate_lead` fires on `/thank-you-als/` and `/thank-you-ipp/?r=<readiness>`.
- Ads conversion import **not wired**. `page-thankyou.php` already has the code — it activates when `UREKA_ADS_ID` and `UREKA_ADS_LABEL` are defined. That is a ~2-minute change once the account is live.
- **Pending MCC link request from "Cyclops Net Inc | MCC".** The same `nox@cyclopsnet.com` is an unexplained manager on Sky Sigma. **Do not accept without Amir confirming who they are** — an MCC link hands over full account control.

## 3. The strategic conclusion (already researched — don't redo it)

Ahrefs, 1 Aug 2026: **there is no bottom-funnel search demand for either programme.** Best ALS term `higher education leadership program` = 70/mo US. `academic leadership programme` = 0. `un youth programme` = 0. `unitar` has 21k global volume but it is navigational traffic looking for UNITAR itself.

So: **demand generation, not demand capture.** Search stays small and defensive (~USD 18/day, exact/phrase). Real budget goes to prospecting creative for 18+ students and parents. The highest-ROI motion is **not advertising at all** — it is institutional delegation outreach (15 students = USD 45,000, faculty travel free, one decision-maker).

**ALS October 2026 is not winnable with paid media** — visas need 6–8 weeks plus CV/SOP/interview, leaving ~6 weeks. Treat ALS as 2027 pipeline.

## 4. Blocking gates — in order

1. **Fix the stale ALS page.** It still promotes the July 2026 cohort (ended 10 July) and an early bird that expired 31 July — 7 references on the live page. Paid traffic to a dead offer is pure waste. *This is the one gate fixable without Amir.*
2. **Amir activates billing** on 798-872-3174.
3. **Wire the Ads conversion** (ID + label into `page-thankyou.php`), assign values (IPP 3,000 / ALS 4,500), verify a real conversion lands before scaling.
4. **Build `/delegations/`** with its own form — institutional buyers currently share the student application wizard, which makes the USD 45,000 lead invisible in reporting.

## 5. Deploy mechanics (this repo bites in specific ways)

- Deploy = cPanel Git: UAPI `/execute/VersionControl/update` then `/execute/VersionControlDeployment/create`, host `bom1plzcpnl503479.prod.bom1.secureserver.net:2083`.
- **The repo is private and the server has no credential**, so `update` fails with `could not read Username for github.com`. Current workaround: flip the repo public on GitHub, pull, deploy, flip back. Permanent fix (deploy key or PAT) still outstanding and needs Amir to paste the secret.
- **`ureka.co.uk` sits behind a GoDaddy bot wall.** Automated wp-admin/admin-ajax/REST requests get "Please wait while your request is being verified…" forever. Do not automate live wp-admin. cPanel is unaffected.
- One deploy publishes **both** staging and live.

## 6. Still outstanding

- 73 Kingster demo pages are drafted on staging only; the live mirror is blocked (bot wall + phpMyAdmin SSO broken: `Access denied for user 'cpses_…'`).
- Rotate the WP admin password (leaked in chat 27 July).
- Confirm sitemap submitted in Search Console.
- From Wael: Olympic Museum photo (or confirm the venue card was intentionally dropped in v4), and Alice Richard's organisation — the page still reads "[Organisation to confirm]".
