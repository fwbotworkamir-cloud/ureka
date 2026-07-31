# Ureka Paid Acquisition Strategy — August 2026

Prepared 1 Aug 2026. Covers IPP (Geneva, Nov–Dec 2026) and ALS (Geneva, Oct 2026).
Account: Google Ads **798-872-3174** ("Ureka Edu corp") — billing not yet active.
Analytics: GA4 **G-0BJ1XCV8LZ**, `generate_lead` firing on `/thank-you-als/` and `/thank-you-ipp/`.

---

## 1. What we are actually selling

| | IPP | ALS |
|---|---|---|
| Audience | Students & emerging leaders, **61% aged 17–22** | Deans, provosts, VCs, education policymakers |
| Price | USD 3,000 (no accom) / 3,550 twin | USD 6,000 (−1,500 merit grant) |
| Format | 3 weeks: 2 remote + Geneva week | 4 days in Geneva |
| Next cohort | Geneva week **29 Nov – 5 Dec 2026** | **27–30 Oct 2026** |
| Hard deadline | Applications close **4 Oct 2026** (UN clearance needs 8 weeks) | Visa needs 6–8 weeks → decisions by **~10 Sept** |
| Hidden channel | **Delegations: 15+ students, faculty travel free → USD 45,000/institution** | — |

## 2. The finding that shapes everything: the search demand isn't there

Ahrefs, checked 1 Aug 2026 (US / global / India):

| Keyword | US vol | Global | Note |
|---|---|---|---|
| `unitar` | 250 | 21,000 | Navigational — people looking for UNITAR itself, not us |
| `unitar courses` | 10 | 1,100 | Mostly free/online UNITAR catalogue seekers |
| `model united nations` | 900 | 5,900 | KD 67; MUN is a cheap school activity — wrong intent for USD 3,000 |
| `youth leadership program` | 300 | 1,000 | Broad, mostly free/domestic programmes |
| `un internship` | 200 | 1,600 | Job-seeking intent, not paid-programme intent |
| `higher education leadership program` | 70 | 70 | Closest ALS term. Seventy. |
| `academic leadership programme` | 0 | 10 | — |
| `geneva summer school` | 0 | 50 | — |
| `un youth programme` | 0 | 0 | — |

**There is no bottom-funnel search market for this.** Nobody wakes up and googles "UN project programme Geneva USD 3,000". A search-first plan would spend almost nothing, look cheap on a CPA report, and sell nothing.

This is a **demand-generation** problem, not a demand-capture problem. Budget must go where we can *create* intent, with Search kept small and purely defensive.

## 3. Recommended allocation

### Tier 1 — Institutional / delegation outreach (highest ROI, zero ad spend)
One delegation = **USD 45,000**, versus USD 3,000 for one student. Same programme, same Geneva week, one decision-maker.
This is outbound and partnerships — international-office heads, IB/international-school leadership, honours-college directors — not advertising. It should be the primary commercial effort.
Ads role: a **retargeting layer** on institutional visitors, plus a dedicated `/delegations/` page to send outreach traffic to.

### Tier 2 — Paid social prospecting for IPP (the real ad budget)
17–22-year-olds and their parents are on Instagram, TikTok and YouTube, not on Google Search.
Creative that works for this product: participant footage inside the Palais des Nations, the certificate moment, the Lake Geneva cruise, alumni talking heads (we now have real photos and testimonials on the page).
Google-native equivalent if we stay in-platform: **Demand Gen** (YouTube + Discover), which is the closest thing Google has to Meta prospecting.

### Tier 3 — Search, small and defensive
| Campaign | Terms | Daily |
|---|---|---|
| Brand | `ureka`, `ureka education`, `international project programme ureka`, `ureka als` | USD 5 |
| UNITAR-adjacent (exact/phrase only) | `unitar programme geneva`, `unitar certificate programme`, `unitar academic leadership` | USD 8 |
| ALS role-intent (exact) | `higher education leadership program`, `executive programme for deans` | USD 5 |

Exact and phrase only. Broad match on this account would burn the budget on UNITAR's free-course traffic and MUN students.

### Suggested 8-week test
| Line | Budget |
|---|---|
| Search (all three campaigns) | ~USD 1,000 |
| Demand Gen / paid social — IPP prospecting | ~USD 2,000 |
| **Total** | **~USD 3,000** |

Break-even is **one IPP enrolment** (USD 3,000). Two enrolments or one delegation makes it clearly profitable.

## 4. Targets

| Metric | IPP | ALS |
|---|---|---|
| Order value | USD 3,000 | USD 4,500 net of grant |
| Max CPA (15% of revenue) | USD 450 | USD 675 |
| Assumed lead→enrol | 10% | 8% |
| **Max cost per lead** | **USD 45** | **USD 54** |

Kill any ad group whose cost-per-lead exceeds 1.5× target for two consecutive weeks.

## 5. Geography

Weight toward low visa-friction and proven markets — the existing cohort skews GCC, India, SE Asia and Central Asia.
Visa rejection carries a **full refund**, so high-refusal-rate markets are a real cost, not just a conversion-rate problem. Start with UAE/GCC, India, Singapore/Malaysia, UK/EU; treat higher-risk markets as a deliberate, separately-budgeted test.

## 6. Gates before a single dollar is spent

1. **Fix the stale ALS offer** — the live page still promotes the July 2026 cohort (ended 10 July) and an early bird that expired 31 July. 7 references on the page.
2. **Wire Ads conversion tracking** — set `UREKA_ADS_ID` / `UREKA_ADS_LABEL` in `page-thankyou.php`. Until then, spend is unmeasurable.
3. **Assign conversion values** (3,000 / 4,500) so bidding can optimise to revenue, not lead count.
4. **Activate billing** on 798-872-3174.
5. **Build `/delegations/`** — institutional traffic currently lands on a section of the IPP page with the same student form.

---

## AUDIT — arguing against the above

### A1. ALS October 2026 is not winnable with paid media. *(Critical)*
Today is 1 Aug. Visas need 6–8 weeks, selection includes a CV, an SOP and an interview. Realistic decision cut-off is ~10 September: **six weeks** to run ads, generate leads, interview, select, invoice and secure visas for a USD 6,000 executive commitment bought from institutional budgets that are set annually.
**Verdict:** do not spend acquisition budget on ALS October. Run ALS as pipeline-building for 2027 cohorts. Reallocate to IPP, whose 4 October deadline is achievable.

### A2. The search plan is honest but nearly pointless. *(High)*
At 70 searches/month for the best ALS term and zero for several others, the Search tier will struggle to spend even USD 18/day. That is fine — brand defence is cheap insurance — but nobody should mistake it for a growth channel or judge the account on its CPA.

### A3. Tier 2 depends on channels not yet set up. *(High)*
The only asset is a Google Ads account, and the strongest recommendation is Meta/TikTok. Either accept a Google-only version (Demand Gen, weaker for this demographic) or budget setup time for Meta Business Manager, pixel and catalogue. **The plan currently hand-waves this.**

### A4. Under-18 targeting is a live compliance issue. *(High)*
61% of participants are 17–22, so a meaningful share are minors. Meta and Google both restrict targeting and data collection for under-18s, and marketing a paid overseas trip to minors carries safeguarding and consumer-protection obligations. Target **18+ and parents**; do not aim creative at 17-year-olds.

### A5. One form cannot serve a USD 3,000 lead and a USD 45,000 lead. *(High)*
A university international-office head enquiring about 15 students goes through the same student application wizard. That is a poor experience for the highest-value buyer and makes the delegation channel invisible in reporting.

### A6. The CPA maths assumes conversion rates we have never measured. *(Medium)*
10% lead→enrol is a guess. The site has had tracked leads only since late July, and I have no closed-loop data. Treat the first four weeks as **measurement**, not optimisation, and revise targets from actual funnel data rather than defending the model.

### A7. "One enrolment breaks even" is technically true and slightly misleading. *(Medium)*
It ignores delivery cost. USD 3,000 of revenue is not USD 3,000 of margin once Geneva delivery, venue access and staff time are counted. The real break-even is higher; Ureka should supply the contribution margin before the budget is judged.

### A8. August is the worst month for institutional outreach. *(Medium)*
Northern-hemisphere universities are on vacation. Tier 1 — the highest-ROI activity — effectively cannot start until late August. Front-load creative production and page-building in August; start outreach in September, which collides with the 4 October deadline. **Tight.**

### A9. Model UN is dismissed too quickly. *(Low)*
Wrong intent for Search, correct *audience*. MUN participants are self-selected into exactly this interest. Useless as a keyword, potentially strong as an interest/lookalike signal on paid social, and worth partnership outreach to MUN conference organisers.

### A10. Retargeting pools will be too small to matter at first. *(Low)*
Both pages are new with minimal traffic. Retargeting needs audience volume; it will not perform until prospecting has run for several weeks. Do not judge it early.

---

## Revised recommendation after the audit

1. **Drop ALS from the paid plan for October.** Fix the page's dates, keep the brand campaign running, treat ALS as 2027 pipeline.
2. **Concentrate everything on IPP** against the 4 October deadline.
3. **Make delegations the priority commercial motion** — build `/delegations/` with its own form, then outbound in September.
4. **Run Search small and defensively** (~USD 18/day), and judge it on brand protection, not volume.
5. **Put the real money into prospecting creative** for 18+ students and parents.
6. **Treat weeks 1–4 as measurement.** Wire conversions with values first; revise all targets from observed data.
