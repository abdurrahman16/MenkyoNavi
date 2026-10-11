# Japanese Karimen — Public Activation

**Status: NOT publicly activated.** No genuine human approvals have been recorded. Research support is not approval.

## Admin routes

| Purpose | Path |
| --- | --- |
| Review index (all 50) | `/admin/questions/original-karimen-pilot` |
| Candidate review + release gates | `/admin/questions/original-karimen-pilot/{entry}` |
| Private 50-question preview | `/admin/questions/original-karimen-exam-preview` |
| Question bank detail (alternate) | `/admin/questions/{entry}` |

Require: verified administrator (`menkyo:grant-admin`).

## Five priority revisions

Process first on each candidate show page:

1. `:1` — Art. 7 subject scope  
2. `:8` — Art. 36 streetcar/vehicle priority  
3. `:26` — Art. 36(2) wording  
4. `:34` — Art. 35(1) lane-direction wording  
5. `:49` — Art. 71(v)-5 device-use wording  

Apply or reject with rationale. Apply updates wording + fingerprint and requires new accuracy review. Reject keeps current wording but resolves the withhold when chosen intentionally. Request/defer leave publication blocked.

## Human approval checklist (per question)

Complete separately on the candidate page (each action audited):

1. Source revision (if clarification candidate)  
2. Accuracy approval for current fingerprint  
3. Answer-key verification  
4. Editorial approval  
5. Rights/provenance clearance  
6. Mark Japanese ready for publication  
7. Publish to live exam pool (only when blockers empty)

One gate never implies another. There is no approve-all control.

## Publish an eligible question

On the candidate page, use **Publish to live exam pool** after blockers are empty. Server re-validates gates, creates/updates a published `Question`, attaches Karimen, writes audit. Refuses when any gate fails.

## Release check

```bash
php artisan menkyo:karimen-release-check
php artisan menkyo:karimen-release-check --json
```

Read-only. Does not mutate records.

## Confirm Japanese public exam is available

- Release check: `Japanese public Karimen available = yes`  
- `/exams/karimen` for a Japanese locale session offers full practice start  
- Full attempt draws 50 published, content-reviewed questions (no imported unpublished bank)

## Recognize unavailable exam

- Release check: available = no  
- Practice page shows shortage / unavailable message  
- Live-pool eligible count &lt; 50

## Still remaining after Japanese activation

- Seven non-Japanese full pools: gated until 50 eligible reviewed translations each (56 drafts remain private)  
- Honmen: 90 standard + 5 illustrated groups still empty  
- Gaimen: 50 published items still empty  
- 1,024 imported questions: private, rights unresolved  

Checklist packet: `docs/KARIMEN_ORIGINAL_50_REVIEW_PACKET.md`
