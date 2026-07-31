# IPP — Paid Acquisition Strategy (standalone)

Split from `ads-strategy.md` 1 Aug 2026 per Amir: IPP and ALS are separate products with
separate strategies. This doc = IPP only. ALS: see `als-strategy.md`.
Keyword data: `ads-keyword-plan.md`. Operating rules: `ADS-SESSION-BRIEF.md`.

---

## 1. Product

| | |
|---|---|
| Offer | International Project Programme — 3 weeks (2 remote + Geneva week), delivered with UNITAR |
| Geneva week | **29 Nov – 5 Dec 2026** (Winter Session) |
| Price | USD 3,000 (no accom) / 3,550 twin |
| Applications close | **4 Oct 2026** (UN security clearance needs 8 weeks) |
| Audience | Students & emerging leaders, 61% aged 17–22 (minors in cohort → ads target 18+ and parents) |
| The hook | Participants **speak at the UN** — "Model UN, but the real room, and more time" |
| Itinerary proof | WHO, ILO, ICRC, CERN, UNOG visits (from ADU delegation posts) |
| Hidden channel | **Delegations: 15+ students → USD 45,000/institution, faculty travel free** |

**Unit economics (placeholder until Ureka supplies contribution margin — audit A7):**
order value $3,000 · max CPA 15% = $450 · assumed lead→enrol 10% (unmeasured, A6) →
**max CPL $45**. Weeks 1–4 = measurement, not optimisation; kill rules live from week 4 only.

## 2. Channel priority (ranked by expected ROI)

### 2.1 Delegations — the primary commercial motion (zero ad spend)
**Proven 1 Aug via LinkedIn:** Abu Dhabi University sent a faculty-accompanied delegation to
IPP July 2026, organised by its Internationalization Office, and publicly invited students to
the November session.
- **Lead #1: re-book ADU for November.** Warmest lead in the pipeline.
- Buyer persona: **university Internationalization Offices + deans**. Secondary: IB/international
  school leadership, honours-college directors, **MUN club advisors** (a school MUN club is a
  pre-built delegation with a travel-authorised advisor).
- Target lists: UAE universities (ADU's peers), Qatar Education City (Georgetown, CMU,
  Northwestern, Texas A&M, Weill Cornell, HEC, UCL — one district), US honours colleges.
- Institutional sanction kills the term-time objection (A10) — this is the only route that
  fully survives the Nov–Dec dates.
- Ads' role: `/delegations/` page (own form + own conversion event, e.g.
  `generate_lead_delegation`) + retargeting layer once pools >300.
- Medium: LinkedIn organic + Wael's-network DMs first (free; Ureka has never run LinkedIn ads —
  ad library empty). LinkedIn Ads to internationalization-office titles only if outreach stalls.
- Timing: outreach from **1 Sept** (universities back from vacation, A8). August = list building
  + `/delegations/` build + ADU case-study deck.

### 2.2 Demand Gen / paid social — the real ad budget (~USD 2,000 of the test)
17–22s and their parents are on IG/TikTok/YouTube, not Search. Creative hook: **"MUN was
practice. This is the real room."** — delegate-at-the-podium shot, Palais footage, certificate
moment, WHO/CERN itinerary, alumni testimonials.
- Google-native: Demand Gen (YouTube + Discover). Needs video in 3 aspect ratios + square/
  landscape images — **August's job is cutting these assets** (A3/M4).
- Audience signals: parents of teens (US), MUN interest, IB schools, university students 18+.
- Sequence: UAE first (home advantage, visa-free nationals), US second (parents; biggest but
  most expensive), Qatar = no cold paid (outreach market).
- Bidding: Maximise Conversions, no target, until ≥4 weeks of conversions (playbook rule).

### 2.3 Search — small real channel (~$56/day IPP share)
Market found 1 Aug (see `ads-keyword-plan.md`): pre-college vocabulary, not UN vocabulary.

| Campaign | Geo | Daily | Notes |
|---|---|---|---|
| IPP Core — leadership/pre-college (non-seasonal) | US | $25 | best fit for Nov cohort |
| IPP Abroad/Exchange | US | $10 | partial 2027-pipeline |
| MUN angle | US+AE | $8 | own the SERP at $0.80; copy gate: confirm "speak at UN" guarantee |
| UAE — IPP | AE | $8 | leadership program dubai / winter camp (teen-qualified) |
| UNITAR probe | US+AE | $5 | exact-only, week-4 quality gate |
| Brand (shared with ALS) | all | $5 | |
| Qatar | — | $0 | do not build |

Exact + phrase only, Maximise Clicks at launch, negatives from day one, weekly search-term audit.
⚠️ Seasonal caveat: "summer …" terms feed **summer-2027 pipeline**, not the 4 Oct deadline.

## 3. Landing pages

- `/ipp/` (v4 live, 136 KB): message-match gaps to fix — parent-facing section (US ads target
  parents), the term-time answer ("miss a week of school in December?": academic value, letter
  for the institution, UNITAR certificate), and the "speak at the UN" claim once confirmed.
- `/delegations/` — **build before September outreach** (Gate 4). Own form + own conversion.
  Lead with ADU case study (get Ureka's OK to name them; posts are public).
- Thank-you: `/thank-you-ipp/?r=<readiness>` fires GA4 `generate_lead` — wire AW- id + label
  (Gate 3), value 3,000.

## 4. Calendar (compressed to reality — M1)

| Week | Work |
|---|---|
| Aug 4–15 | Gates: conversion wiring prep, `/delegations/` build, Demand Gen video cut, delegation target list, ADU deck |
| Aug 18 | Launch Search (all IPP campaigns) + Demand Gen UAE — pending billing (Amir) |
| Aug 25 | Add US Demand Gen (parents) |
| Sept 1 | Delegation outreach wave 1 (ADU re-book first). Weekly funnel review starts |
| Sept 15 | First optimisation pass; kill rules active from here |
| Sept 22 – Oct 4 | Deadline creative ("applications close 4 Oct"); retargeting if pools >300 |

Effective selling window: **4–5 weeks**. The "8-week test" framing is dead — this is a 6-week
sprint with weekly gates.

## 5. Open questions for Ureka

1. Is "speak at the UN" guaranteed per participant or selective? (ad-copy compliance)
2. Contribution margin per enrolment? (real max CPA)
3. Are the Geneva dates fixed? (A10)
4. Nationality qualification on the form: yes/no? (expat visa caveat)
5. OK to name ADU in the delegation deck / `/delegations/` page?
