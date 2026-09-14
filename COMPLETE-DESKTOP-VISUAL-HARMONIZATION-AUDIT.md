# COMPLETE DESKTOP VISUAL HARMONIZATION AUDIT

## A. INSTRUCTIONS / SKILLS

### Files read

- [AGENTS.md](AGENTS.md)
- [DESIGN.md](DESIGN.md)
- [COMPLETE-MOBILE-UI-UX-AUDIT.md](COMPLETE-MOBILE-UI-UX-AUDIT.md)
- [COMPLETE-MOBILE-UI-REDESIGN-IMPLEMENTATION.md](COMPLETE-MOBILE-UI-REDESIGN-IMPLEMENTATION.md)
- Dokumentasi domain, architecture, security, integration, route UX, dan implementation status di `docs/`.

Laporan terpisah bernama “FINAL STRONG MOBILE VISUAL REDESIGN” tidak ditemukan sebagai artefak repository. Karena itu, implementasi mobile aktif di Blade/CSS/JavaScript diperlakukan sebagai bukti authoritative untuk visual language terbaru.

### Skills loaded

- `.codex/skills/antislop/ANTISLOP.md`
- `.codex/skills/antislop/SKILL.md`
- `.codex/skills/antislop-ui/SKILL.md`
- `.codex/skills/antislop-layoutmobile/SKILL.md` — referensi untuk memahami keputusan mobile, bukan untuk menerapkan layout mobile pada desktop.

`antislop-copywriting` tidak dimuat karena audit tidak menemukan kebutuhan perubahan copy material. `antislop-human` juga tidak diperlukan.

### Source areas inspected

- 76 route aplikasi.
- Public, Client, guest, Admin, dan Admin-auth layouts.
- Header, sidebar, bottom navigation, icon, button, progress, status, form, document, feedback, dan application-list components.
- Client dashboard, services, application listing/create/workspace, payment, Help, chat, auth, dan compatibility registration screens.
- Admin dashboard, queues, detail/review, support, users, activity, chat, dan authentication.
- `resources/css/app.css`, `resources/css/phase-b.css`, dan relevant presentation JavaScript.

### Rendered inspection

**VISUAL QA NOT VERIFIED.**

Tidak ada browser atau tab yang tersedia pada computer tooling. Audit visual ini berbasis struktur dan style source. Semua penilaian yang memerlukan computed layout tetap berstatus **RENDER RISK** sampai diuji pada 1024, 1280, 1366, 1440, dan 1920 px.

---

## B. EXECUTIVE SUMMARY

### Current Desktop condition

**Public Desktop**

Public landing sudah lebih matang daripada asumsi “semuanya putih dan kering”. Hero dua kolom, ilustrasi, benefit icons, step composition, closing CTA navy, dan footer telah membentuk identitas yang jelas. Gap utamanya adalah bagian tengah yang terlalu bergantung pada kumpulan card putih seragam dan penggunaan aksen semantik yang belum konsisten.

**Client Desktop**

Client Desktop secara struktural kuat: shell horizontal jelas, dashboard memiliki focal next-action surface, workspace memanfaatkan main-plus-sidebar, payment memakai dua kolom, dan progress enam tahap sesuai ruang desktop.

Gap terbesar terhadap mobile adalah:

- navigation masih text-only;
- document states belum memiliki diferensiasi visual sekuat mobile;
- activity, Help, application records, dan supporting workspace surfaces terlalu seragam;
- status masih terlalu pill-oriented;
- focus-visible Client/Public terlalu lemah;
- beberapa halaman memakai card sebagai alat hierarchy utama.

**Admin Desktop**

Admin paling matang secara operasional. Sidebar, tabel, filter, detail layout, sticky action sidebar, support rows, dan dense information presentation patut dipertahankan.

Gap utamanya:

- keluarga ikon sidebar tidak konsisten;
- dashboard dan application detail terlalu banyak menggunakan white surfaces dengan visual weight setara;
- status desktop belum memakai clarity model mobile berupa color + text + compact marker;
- attention items belum cukup membedakan jenis pekerjaan;
- history application detail dapat muncul terlalu dini akibat ordering class yang tidak lengkap.

### Largest harmonization gaps

1. Client desktop navigation belum memakai icon family mobile.
2. Focus-visible Public/Client belum cukup kuat.
3. Document and payment state semantics mobile belum ditransfer ke desktop.
4. Client workspace dan Admin detail masih memiliki terlalu banyak equal-weight white surfaces.
5. Admin sidebar memakai campuran exported filled SVG dengan weight dan warna yang tidak konsisten.
6. Status desktop terlalu sering berbentuk pill tanpa marker semantik.
7. CSS ownership antara `app.css`, `phase-b.css`, shared chat, dan mobile icon belum cukup aman untuk harmonisasi lintas viewport.

### Strong Desktop patterns to preserve

- Public two-column hero.
- Client horizontal top navigation architecture.
- Client dashboard next-action focal surface.
- Desktop six-stage application progress.
- Workspace main-plus-sticky-sidebar.
- Payment two-column composition.
- Admin fixed sidebar and sticky header.
- Desktop operational tables.
- Three-column Admin document evidence layout.
- Admin support row list.
- Native browser handling for private evidence.
- Existing button family and restrained border-radius language.

### Recommended direction

Desktop perlu mengadopsi **semantic language**, bukan mobile composition:

- warna memiliki fungsi domain yang sama;
- satu stroke-based functional icon family;
- focal surfaces dipakai secara selektif;
- status lebih compact dan tidak bergantung pada warna;
- row/list hierarchy menggantikan sebagian card repetition;
- desktop tetap lapang, horizontal, dan operational.

---

## C. CURRENT MOBILE VISUAL LANGUAGE

Mobile aktif menggunakan:

- **Navy/brand blue** untuk primary action, identity, dan focal workflow.
- **Sky blue** untuk selected atau interactive context.
- **Cyan** untuk Help/support.
- **Mint** untuk uploaded, accepted, ready, dan success.
- **Amber** untuk waiting, expiry, consent, dan attention.
- **Soft lilac** untuk secondary identity/user context.
- **Red** untuk rejection, error, dan destructive action.
- Stroke-based inline SVG icons yang mengikuti `currentColor`.
- Icon + label bottom navigation dengan empat tujuan.
- Compact status dengan text dan marker, bukan pill besar.
- Satu focal colored surface pada halaman penting.
- Record rows, activity rows, dan operational units yang lebih compact.
- Reduced nested cardification.
- Form fields dengan relationship label/control/helper/error yang lebih jelas.
- Mobile workspace sebagai guided task hierarchy.
- Domain-specific document and payment states.
- Client lebih expressive; Admin mobile lebih restrained.

Prinsip ini layak dibawa ke desktop. Layout satu kolom, bottom navigation, disclosure agresif, dan tombol full-width tidak layak ditransfer langsung.

---

## D. DESKTOP INVENTORY

### PUBLIC

| Route/surface | Desktop composition | Main components | Status |
|---|---|---|---|
| `/` | Header, two-column hero, benefits, services, process, testimonial, FAQ, closing CTA, footer | `site-header`, landing sections | Source inspected; visual QA not verified |
| `/qna` guest | Search, categories, FAQ, guest support/login entry | QNA view, FAQ disclosure | Source inspected; visual QA not verified |
| `/register` | Intro and registration form surface | Shared controls/buttons | Source inspected; visual QA not verified |
| `/login` | Brand/illustration and authentication form | Auth layout | Source inspected; visual QA not verified |
| `/forgot-password` | Centered recovery flow | Guest/auth components | Source inspected; visual QA not verified |
| `/reset-password/{token}` | Centered reset flow | Guest/auth components | Source inspected; visual QA not verified |
| `/auth/otp` | Single-purpose verification surface | OTP form | Source inspected; visual QA not verified |

### CLIENT

| Route/surface | Desktop composition | Main components | Status |
|---|---|---|---|
| `/app/dashboard` | Focal next action, applications/activity columns, secondary services | Client shell, application rows | Source inspected; visual QA not verified |
| `/app/services` | Two-column service cards | Service status/action content | Source inspected; visual QA not verified |
| `/app/applications` | Filters and application record surface | `application-list-item` | Source inspected; visual QA not verified |
| `/app/applications/create/{service}` | Focal summary plus create/consent area | Buttons, form controls | Source inspected; visual QA not verified |
| `/app/applications/{publicId}` | Identity/progress header, main content, sticky aside | Progress, Livewire form, documents, face photo | Source inspected; visual QA not verified |
| `/app/bayar/{publicId}` | Order/payment summary and method/instruction surface | Payment form/status | Source inspected; visual QA not verified |
| `/qna` authenticated | Search/FAQ plus application support | Help and chat entry | Source inspected; visual QA not verified |
| `/app/chat/{publicId}` | Thread surface and composer | Shared Livewire chat thread | Source inspected; visual QA not verified |
| `/npwp-pribadi`, `/npwp-badan`, `/jenis-badan` | Compatibility registration entry flows | Registration views/components | Source inspected; visual QA not verified |
| Private document/result routes | Native browser evidence delivery | Authorized controllers | UI shell not applicable |

### ADMIN

| Route/surface | Desktop composition | Main components | Status |
|---|---|---|---|
| `/admin/login`, `/admin/otp` | Restrained centered auth surface | Admin-auth layout | Source inspected; visual QA not verified |
| `/admin/dashboard` | Attention panel, chart, recent work, priority queue | Metrics, chart/feed | Source inspected; visual QA not verified |
| `/admin/applications` | Filters and operational desktop table | Livewire application queue | Source inspected; visual QA not verified |
| `/admin/applications/{publicId}` | Identity/status, main evidence sequence, sticky operation aside | Review and document units | Source inspected; visual QA not verified |
| `/admin/documents` | Document-readiness table | Livewire document queue | Source inspected; visual QA not verified |
| `/admin/support` | Compact conversation rows | Livewire support inbox | Source inspected; visual QA not verified |
| `/admin/chat/{publicId}` | Shared chat nested in Admin shell | Livewire chat thread | Source inspected; visual QA not verified |
| `/admin/users` | Search/filter and directory table | Livewire user directory | Source inspected; visual QA not verified |
| `/admin/users/{publicId}` | Identity and application context | User detail view | Source inspected; visual QA not verified |
| `/admin/activity` | Filtered chronological data table | Livewire activity feed | Source inspected; visual QA not verified |

---

## E. PUBLIC DESKTOP FINDINGS

### Public header/navigation

- **Current:** Sticky white header with logo, text navigation, login and register actions.
- **Strength:** Clear marketing navigation; restrained; does not imitate an application shell.
- **Gap vs Mobile:** Active, hover, and focus hierarchy are weaker. Icons would add clutter to marketing anchors such as “Cara Kerja”.
- **Recommendation:** Keep navigation text-dominant. Harmonize focus ring, hover color, and button semantics only. Do not add icons to every public link.
- **Classification:** KEEP / selective ADOPT.
- **Priority:** P2 for focus; P3 for color refinement.

### Hero

- **Current:** Strong two-column presentation with headline, wordmark, CTA, and illustration.
- **Strength:** Already has clear focal hierarchy and product identity.
- **Gap vs Mobile:** Minimal. Supporting label is pill-like and could compete with other badge language.
- **Recommendation:** Preserve composition. Future work may refine label shape and use a controlled sky accent, but should not rebuild the hero.
- **Classification:** KEEP.
- **Priority:** P3.

### Services and benefits

- **Current:** Benefits have functional icons; services use large, equal white cards with circular icon treatments.
- **Strength:** Clear comparison and service discovery.
- **Gap vs Mobile:** Services feel more generic and equal-weight; service identity accents are weaker.
- **Recommendation:** Adapt mobile service identity accents into small icon/title zones while retaining desktop columns. Avoid independently colored entire cards.
- **Classification:** ADAPT.
- **Priority:** P2.

### Process/testimonial

- **Current:** Structured steps and testimonial surface.
- **Strength:** Steps have a justified visual sequence.
- **Gap vs Mobile:** Limited.
- **Recommendation:** Preserve. Only align icon weight, dividers, and supporting metadata with the shared system.
- **Classification:** KEEP.
- **Priority:** P3.

### FAQ, closing CTA, and footer

- **Current:** FAQ is visually grouped; closing CTA already provides a strong navy focal ending.
- **Strength:** Effective section closure.
- **Gap vs Mobile:** FAQ rows can inherit clearer chevrons, focus-visible, and lighter separators.
- **Recommendation:** Adopt Help interaction semantics, not the entire cyan mobile composition.
- **Classification:** ADAPT.
- **Priority:** P2/P3.

---

## F. CLIENT DESKTOP FINDINGS

### Shell and navigation

- **Current:** Horizontal text navigation, avatar/account control, active pale-blue fill and bottom indicator.
- **Strength:** Correct desktop architecture; sufficient room for four primary destinations.
- **Gap vs Mobile:** Recognition is slower than icon + label mobile navigation, and the two form factors feel visually disconnected.
- **Recommendation:** Add restrained 17 px functional icons before labels. Retain labels as primary information. Strengthen focus-visible to 2 px with offset.
- **Classification:** ADOPT icon semantics; ADAPT presentation.
- **Priority:** P2.

### Dashboard

- **Current:** Navy next-action surface, application list, latest updates, secondary services.
- **Strength:** The primary action is already visibly dominant.
- **Gap vs Mobile:** Activity remains more card-like and lacks semantic markers; secondary services are still visually generic.
- **Recommendation:** Preserve focal next action. Adapt compact activity rows with small semantic markers and dividers. Give the service area one restrained contextual accent.
- **Classification:** KEEP focal surface; ADAPT supporting content.
- **Priority:** P2.

### Services

- **Current:** Two large service cards with similar hierarchy, generous padding, and nested active-application context.
- **Strength:** Desktop comparison works well.
- **Gap vs Mobile:** Equal card treatment weakens service identity and increases perceived repetition.
- **Recommendation:** Retain the two-column grid but differentiate the title/icon zone and integrate active application state without another prominent nested card.
- **Classification:** ADAPT.
- **Priority:** P2.

### Application listing

- **Current:** Filter chips, an outer results surface, and individually bordered application cards.
- **Strength:** All record information and actions remain available.
- **Gap vs Mobile:** Outer container plus record cards creates avoidable cardification.
- **Recommendation:** Use one list ownership surface and rows separated by dividers. Preserve richer horizontal metadata than mobile. Strengthen service, status, update time, and action hierarchy.
- **Classification:** RESTRUCTURE through ADAPT.
- **Priority:** P2.

### Create/preflight

- **Current:** Two-column layout with a strong navy service summary and create/consent area.
- **Strength:** Purposeful desktop composition and clear decision boundary.
- **Gap vs Mobile:** Consent/privacy context lacks the semantic amber treatment used on mobile.
- **Recommendation:** Keep layout. Adopt the restrained amber consent/context treatment and shared service iconography.
- **Classification:** KEEP / ADOPT.
- **Priority:** P3.

### Application workspace

- **Current:** Integrated identity/status/progress header, six-stage progress, content column, and sticky 290 px sidebar.
- **Strength:** Correct desktop density; desktop stepper is justified; supporting context remains visible while working.
- **Gap vs Mobile:** Most main and sidebar sections use equivalent white-card weight. Help, summary, history, and active task are insufficiently differentiated.
- **Recommendation:** Keep columns and six-step progress. Use one stronger active-task surface, cyan only for Help, neutral/flat treatments for read-only metadata, and lower visual weight for repeated summary/status.
- **Classification:** ADAPT.
- **Priority:** P2.

### Documents and face photo

- **Current:** Meaningful document units in a desktop grid, actions, preview, and face-photo area.
- **Strength:** Requirements and operations remain local.
- **Gap vs Mobile:** Empty, uploaded, scanning, ready, rejected, and replace-required states are not visually distinct enough. Uploaded content can retain empty-dropzone characteristics.
- **Recommendation:** Adopt mobile semantic state colors, icons, compact uploaded-file state, and action hierarchy. Preserve grid, private-file actions, and evidence rules.
- **Classification:** ADOPT state semantics; ADAPT density.
- **Priority:** P2.

### Payment

- **Current:** Strong two-column summary/action composition and compact selectable payment methods.
- **Strength:** The layout already resembles a mature desktop payment flow.
- **Gap vs Mobile:** Amount, actual payment instruction, VA/QR area, and expiry do not consistently receive the semantic emphasis available on mobile. Status can repeat.
- **Recommendation:** Keep columns. Adopt strong amount hierarchy, blue-soft instruction surface, amber expiry, and restrained payment-status marker.
- **Classification:** KEEP / ADOPT.
- **Priority:** P2.

### Help/QNA

- **Current:** Prominent search, categories, FAQ, and application support/sidebar composition.
- **Strength:** Desktop can expose knowledge and support simultaneously.
- **Gap vs Mobile:** Search, FAQ, and direct support lack clear contextual separation.
- **Recommendation:** Use cyan as the Help/support context, not as a generic page background. Keep FAQ row-led with separators and reserve a contextual surface for application support.
- **Classification:** ADAPT.
- **Priority:** P2.

### Chat

- **Current:** Shared thread card, message scroller, bubbles, metadata, and composer.
- **Strength:** Behavior is shared and authoritative.
- **Gap vs Mobile:** Outer page frame plus chat frame can become visually nested, particularly under Admin.
- **Recommendation:** Establish one visible thread boundary, adopt restrained mobile bubble differentiation, and preserve desktop width and message density.
- **Classification:** ADAPT.
- **Priority:** P3.

### Auth and registration

- **Current:** Login uses a composed desktop split; registration and verification flows use centered surfaces.
- **Strength:** Authentication context remains clear and trustworthy.
- **Gap vs Mobile:** Secondary registration/recovery actions can look like nested cards; form focus/error states are inconsistent.
- **Recommendation:** Preserve desktop compositions. Adopt mobile field rhythm, focused accent, compact secondary actions, and consistent error/focus treatment.
- **Classification:** KEEP / ADAPT.
- **Priority:** P2 for focus; otherwise P3.

---

## G. ADMIN DESKTOP FINDINGS

### Shell/sidebar/header

- **Current:** Fixed 232 px sidebar, six icon + label destinations, sticky 76 px header.
- **Strength:** Appropriate operational architecture.
- **Gap vs Mobile:** Existing exported SVGs use inconsistent visual styles. Dashboard icon has different geometry and hardcoded blue fill, while other icons are heavier gray filled assets.
- **Recommendation:** Converge on one `currentColor`, 18–19 px, 1.8-ish stroke icon family shared conceptually with mobile. Retain sidebar architecture.
- **Classification:** ADOPT icon family; ADAPT sidebar states.
- **Priority:** P2.

### Dashboard

- **Current:** Attention metrics, chart, recent activity, and priority table.
- **Strength:** Desktop uses horizontal space appropriately.
- **Gap vs Mobile:** Attention items and major surfaces carry similar visual weight.
- **Recommendation:** Keep chart and layout. Use restrained semantic markers for attention categories and give priority work stronger hierarchy than trend information.
- **Classification:** ADAPT.
- **Priority:** P2.

### Application queue

- **Current:** Desktop table with filters, metadata, statuses, and review action.
- **Strength:** Table is correct for comparison and bulk scanning.
- **Gap vs Mobile:** Status/action recognition is more textual; hover and attention state are subtle.
- **Recommendation:** Keep table. Add compact status marker, stronger row hover/focus, and a clearer review-action hierarchy. Do not replace with mobile operational cards.
- **Classification:** KEEP / ADAPT.
- **Priority:** P2.

### Document queue

- **Current:** Dedicated data table for document readiness and review counts.
- **Strength:** Different operational purpose from the application queue is preserved.
- **Gap vs Mobile:** Readiness, rejected, and waiting-review states need clearer semantic distinction.
- **Recommendation:** Keep the table but adopt document-specific mint/amber/red markers and action hierarchy.
- **Classification:** ADOPT semantics; KEEP composition.
- **Priority:** P2.

### Application detail/review

- **Current:** Identity and status header, dense evidence sequence, and sticky next-operation sidebar.
- **Strength:** Evidence, local decisions, and final actions remain deliberate.
- **Gap vs Mobile:** Most sections share the same white bordered surface. History lacks the same ordering modifier as other sections and can be grouped before higher-priority evidence because siblings use explicit order values.
- **Recommendation:** Preserve columns, sticky operation area, and review order. Visually differentiate current operation, evidence, payment, and read-only history. Correct the presentation ordering during implementation without changing workflow semantics.
- **Classification:** ADAPT.
- **Priority:** P2.

### Document review

- **Current:** Three-column document units with status, evidence preview, metadata, local actions, and version disclosure.
- **Strength:** High operational density and evidence/action proximity.
- **Gap vs Mobile:** State semantics are not as immediate as on mobile.
- **Recommendation:** Adopt mobile status colors and icon family while keeping previews neutral and ensuring “open evidence” remains the primary inspection action.
- **Classification:** ADOPT / KEEP.
- **Priority:** P2.

### Support

- **Current:** Compact row list with identity, context, snippet, time, unread treatment, and archive control.
- **Strength:** Appropriate, efficient, and already anti-cardification.
- **Gap vs Mobile:** Support identity and unread emphasis can be more consistent with cyan support semantics.
- **Recommendation:** Keep the row list. Add restrained support icon/accent at the page or thread level; do not color every row.
- **Classification:** KEEP / selective ADOPT.
- **Priority:** P3.

### Users

- **Current:** Searchable/filterable directory table and detail screen.
- **Strength:** Appropriate lookup model; privacy masking remains intact.
- **Gap vs Mobile:** Identity presentation is generic.
- **Recommendation:** Use lilac only for an avatar/identity marker, not as status color. Retain table and compact detail layout.
- **Classification:** ADAPT.
- **Priority:** P3.

### Activity

- **Current:** Filterable data table with actor, action, context, and timestamp.
- **Strength:** Desktop comparison remains useful.
- **Gap vs Mobile:** Chronology and action category are less immediately readable.
- **Recommendation:** Add a small functional event/category marker and improve time hierarchy. Do not convert the desktop table into a social timeline.
- **Classification:** ADAPT.
- **Priority:** P3.

### Admin chat and auth

- **Current:** Shared chat inside Admin shell; restrained centered authentication.
- **Strength:** Functional and appropriately professional.
- **Gap vs Mobile:** Chat can become nested inside two equal surfaces; focus styles need unification.
- **Recommendation:** Keep layouts. Reduce redundant framing and adopt shared bubble/focus semantics.
- **Classification:** KEEP / ADAPT.
- **Priority:** P3.

---

## H. DESKTOP NAVIGATION ICON STRATEGY

### Public navigation

Do not add icons to routine marketing anchors. Public navigation should remain text-dominant.

### Client authenticated navigation

| Destination | Icon | Desktop guidance |
|---|---|---|
| Beranda | Home | 17 px, stroke, before label |
| Layanan | Grid/services | 17 px, stroke, before label |
| Pengajuan | File/document | 17 px, stroke, before label |
| Bantuan | Help-circle/chat | 17 px, stroke, before label |

Rules:

- `currentColor`, consistent 1.75–1.8 stroke.
- Gap between icon and label: approximately 7–8 px.
- Labels remain visible and visually primary.
- Active: brand-blue text/icon, soft-sky background, existing restrained bottom indicator.
- Hover: pale sky/neutral surface; no scale or motion gimmick.
- Focus-visible: 2 px high-contrast outline with offset.
- Unread count must reuse the existing global notifier. No second poller.
- Avatar remains sufficient for account identity; another account icon is unnecessary.

### Admin sidebar

Recommended semantic set:

- Dashboard: overview/home.
- Pengajuan: clipboard/file.
- Dokumen: document stack.
- Dukungan: message/help.
- Aktivitas: activity/history.
- Pengguna: users/person.

Use 18–19 px current-color stroke icons with approximately 11 px label gap. Active state should combine blue icon/text, soft-sky background, and a restrained 2–3 px navigation rail. Avoid pill-shaped sidebar items.

---

## I. COLOR HARMONIZATION STRATEGY

| Family | Meaning | Suitable Desktop use | Do not use for |
|---|---|---|---|
| Navy | Brand identity, primary workflow focus | Primary task surface, major identity header, closing CTA | Every section/card |
| Brand blue | Primary actions, active navigation, key links | Buttons, selected state, focus context | Generic decoration |
| Sky | Selected/interactive/informational context | Active nav/filter, selected payment method, active application | Unrelated page backgrounds |
| Cyan | Help and communication | Help search/support context, chat identity | Generic information messages |
| Mint | Ready, uploaded, accepted, completed | Document readiness, verified/completed state | Marketing embellishment |
| Amber | Waiting, expiry, consent, attention | Payment expiry, review-needed, consent notice | Every pending-looking metadata item |
| Lilac | Secondary identity context | User/avatar and directory identity markers | Success, payment, workflow stage |
| Red | Error, rejected, destructive | Validation, rejection, delete/cancel | Neutral attention |

Desktop should use fewer large colored areas than mobile. A page should normally have one dominant color focal surface and small semantic accents elsewhere.

---

## J. SURFACE / CARD STRATEGY

### ADOPT

- Semantic document-state surfaces.
- Blue-soft selected state.
- Cyan Help context.
- Amber expiry/attention context.
- Compact status marker.
- One clear focal surface per major workflow page.

### ADAPT

- Mobile activity rows into roomier desktop rows.
- Mobile primary-task surface into a horizontal desktop summary.
- Compact mobile application records into desktop rows with richer metadata.
- Mobile Help grouping into desktop columns.
- Mobile payment emphasis into two-column desktop composition.
- Mobile document states into desktop grids.

### AVOID

- Stretching mobile cards across desktop.
- Bottom navigation.
- Single-column workspace.
- Phone-oriented disclosure defaults for essential evidence.
- Full-width desktop CTAs.
- Replacing Admin tables with cards.
- Strong color on every section.
- Combining icon, badge, shadow, border, and tinted background on the same ordinary record.

### Existing desktop containers

- **KEEP:** workspace outer columns, payment summary/action columns, Admin tables, evidence grids.
- **REFINE:** dashboard supporting surfaces, Help, services, Admin attention panel.
- **REDUCE:** nested application list framing, chat outer-plus-inner frame, repeated workspace summary cards.
- **RESTRUCTURE:** only visual ordering/grouping in Admin application detail; business order remains unchanged.

---

## K. BUTTON / STATUS / FILTER STRATEGY

### Buttons

- Primary remains solid brand blue.
- Secondary uses border or soft-blue surface.
- Tertiary remains compact text action.
- Destructive remains red and spatially separated.
- Add icons only when they clarify an operation: open, download, copy, review, search, or delete.
- Desktop buttons should generally remain content-width.
- Preserve minimum practical target size and loading/disabled semantics.

### Status

- Use a compact 7–8 px radius rather than defaulting to full pills.
- Combine readable text with a small dot/icon for important operational states.
- Application, payment, document, review, and result remain separate semantic families.
- Never rely on color alone.
- Avoid repeating the same status in header, card title, and metadata row.

### Filters/search

- Preserve horizontal desktop controls.
- Selected filter: soft-sky fill, stronger blue border/text.
- Hover must be distinct from selected.
- Focus-visible must remain visible independently of hover.
- Search can use a restrained leading search icon.
- Pagination remains conventional; no mobile carousel treatment.

---

## L. TYPOGRAPHY / SPACING STRATEGY

### Typography

- Page context/kicker: small and restrained; avoid excessive uppercase.
- Page title: typically 30–36 px Client/Public, 28–32 px Admin.
- Primary task title: visually strong but below marketing hero scale.
- Section heading: 18–22 px.
- Record title: 14–16 px semibold.
- Body: 14–16 px with readable line height.
- Metadata: 12–13 px, secondary contrast.
- Status: compact but not smaller than readable operational text.

Avoid several large headings stacked above the first actionable content.

### Spacing

- Maintain current generous desktop outer gutters and approximately 1200 px content width.
- Related label/value/action content should use tight spacing.
- Major task sections receive larger separation.
- Do not inflate card padding simply because 1920 px is available.
- Prefer intentional grid width and max-width over extremely long text lines.
- Admin should remain denser than Client.

---

## M. ICONOGRAPHY SYSTEM

### Existing icons to reuse

The current mobile inline icon language is the strongest basis:

- simple outline;
- `currentColor`;
- consistent viewbox;
- approximately 1.8 stroke;
- no dependency required.

### Recommended architecture

Create or evolve toward a role-neutral shared functional icon component rather than exposing a component named `mobile-icon` directly as the desktop API. The same SVG paths may be reused.

### Desktop sizes

- Client top navigation: 17 px.
- Admin sidebar: 18–19 px.
- Buttons/actions: 16 px.
- Section/task context: 18–20 px.
- Service/document identity: 20–24 px where scanning benefits.
- Status marker: 6–8 px dot or 14–16 px semantic icon.

### Icons add value in

- authenticated navigation;
- service identity;
- document identity/state;
- payment method and copy action;
- activity categories;
- Help/support context;
- Admin queue actions;
- open/download/review actions.

### Icons should not be added to

- every marketing navigation link;
- every heading;
- paragraphs and helper text;
- status that already has sufficient label and marker;
- decorative empty corners of cards.

---

## N. KEEP / ADOPT / ADAPT / AVOID MATRIX

| Area | Decision | Reason |
|---|---|---|
| Public two-column hero | KEEP | Already clear and brand-specific |
| Public text navigation | KEEP | Marketing navigation does not need app-style icons |
| Client desktop top-nav architecture | KEEP | Correct for four destinations |
| Client nav icon semantics | ADOPT | Improves recognition and mobile/desktop consistency |
| Mobile bottom navigation | AVOID on desktop | Wrong form-factor pattern |
| Dashboard focal next action | KEEP | Already provides primary hierarchy |
| Mobile activity markers | ADAPT | Useful with desktop row spacing |
| Mobile single-column workspace | AVOID | Loses desktop information density |
| Desktop six-stage progress | KEEP | Appropriate at desktop width |
| Mobile document state semantics | ADOPT | Clear domain communication |
| Desktop payment columns | KEEP | Strong desktop composition |
| Mobile amount/expiry emphasis | ADOPT | Same payment meaning |
| Admin sidebar architecture | KEEP | Operationally appropriate |
| Mobile/current-color icon family | ADOPT | Resolves inconsistent exported assets |
| Admin mobile record cards | AVOID on desktop | Tables are superior for comparison |
| Mobile semantic status system | ADAPT | Use denser desktop form |
| Cyan Help context | ADAPT | Use selectively, not entire page |
| Lilac identity treatment | ADAPT | Limit to identity, not status |
| Strong gradient on many surfaces | AVOID | Creates decoration noise |
| Full-pill status/navigation everywhere | AVOID | Weakens hierarchy |

---

## O. PRIORITY MATRIX

### P0

None.

No source-confirmed desktop visual blocker was found.

### P1

None.

Current desktop remains operational. The harmonization gaps are meaningful but do not block major workflows.

### P2

- Client top navigation icon + label harmonization.
- Stronger Public/Client focus-visible states.
- Shared semantic document states on desktop.
- Client application list nested-card reduction.
- Dashboard activity and supporting-content hierarchy.
- Workspace focal/supporting surface differentiation.
- Payment amount/instruction/expiry hierarchy.
- Help/support contextual differentiation.
- Admin sidebar icon-family normalization.
- Admin dashboard attention hierarchy.
- Admin queue status/action clarity.
- Admin detail surface hierarchy and history ordering.
- Shared status system refinement.
- CSS/icon ownership required to protect the approved mobile design.

### P3

- Public service card refinement.
- Public FAQ chevrons/dividers.
- Auth secondary action refinement.
- Chat framing reduction.
- Admin support cyan context.
- User identity accent.
- Activity event markers.
- Typography and hover-state consistency.
- Removal of redundant pill-like presentation where it adds no meaning.

---

## P. IMPLEMENTATION DEPENDENCY MAP

1. **Shared icon foundation**
   - Establish a role-neutral current-color SVG API.
   - Dependents: Client nav, Admin sidebar, buttons, documents, activity, Help, payment.

2. **Shared semantic presentation**
   - Define desktop usage rules for sky/cyan/mint/amber/lilac/red.
   - Align status, focus-visible, filters, and buttons.
   - Dependents: nearly all Client/Admin operational surfaces.

3. **Shells**
   - Client header/navigation.
   - Admin sidebar/header.
   - Public header focus/hover only.

4. **Client high-reuse records**
   - Application rows.
   - Activity rows.
   - Service identity.
   - Dependents: dashboard, services, application listing.

5. **Client workflow surfaces**
   - Workspace, form, documents, face photo, payment.
   - Security-sensitive markup must preserve actions, bindings, private routes, and state presenters.

6. **Help/chat/auth**
   - Role-specific visual skin while shared chat behavior remains owned once.

7. **Admin operational screens**
   - Dashboard, queues, detail/review, support, users, activity.
   - Keep desktop tables.

8. **Regression phase**
   - Verify 430/431, intermediate Client/Public switches, 1023/1024 Admin switch, and all requested desktop widths.

### CSS ownership

- `phase-b.css`: Public, Client, and Client-auth desktop visual layer.
- `app.css`: Admin desktop visual layer and shared structural behavior.
- Shared chat sizing/behavior should keep one owner; role-specific desktop appearance may be layered without competing height rules.
- Avoid styling old/dead legacy selectors merely because they share a prefix.

---

## Q. LIKELY FILES TO CHANGE

| File | Expected desktop change | Mobile regression risk | Business/security sensitivity |
|---|---|---:|---:|
| `resources/css/phase-b.css` | Client/Public desktop colors, nav, surfaces, states, typography | High | Low |
| `resources/css/app.css` | Admin desktop system, shared focus/status/icon rules | High | Low |
| `resources/views/components/mobile-icon.blade.php` | Generalize or reuse SVG path vocabulary | High | Low |
| `resources/views/components/client-header.blade.php` | Add restrained desktop navigation icons | High | Low |
| `resources/views/components/site-header.blade.php` | Focus/hover harmonization | Medium | Low |
| `resources/views/components/admin/sidebar.blade.php` | Replace inconsistent image icons with shared icon family | High | Low |
| `resources/views/components/admin/header.blade.php` | Context/icon alignment if required | Medium | Low |
| `resources/views/components/button.blade.php` | Shared icon gap/focus behavior if warranted | High | Medium |
| `resources/views/components/admin/status-badge.blade.php` | Compact text + marker semantics | High | Medium |
| `resources/views/components/application-list-item.blade.php` | Desktop record hierarchy and reduced nesting | High | Medium |
| `resources/views/components/application-progress.blade.php` | Visual harmonization only; retain desktop six-stage model | High | High |
| `resources/views/client/dashboard.blade.php` | Activity rows and supporting-section hierarchy | High | Medium |
| `resources/views/client/services/index.blade.php` | Service identity and active-state composition | High | Medium |
| `resources/views/client/applications/index.blade.php` | List ownership and filters | High | Medium |
| `resources/views/client/applications/create.blade.php` | Consent/service accent alignment | High | High |
| `resources/views/client/applications/show.blade.php` | Workspace surface hierarchy | High | High |
| `resources/views/components/personal-document-card.blade.php` | Shared document-state presentation | High | High |
| `resources/views/components/personal-face-document.blade.php` | Shared identity/state presentation | High | High |
| `resources/views/livewire/application-details-form.blade.php` | Section/error/focus presentation | High | High |
| `resources/views/client/payment/show.blade.php` | Amount, instruction, selected method, expiry hierarchy | High | Very high |
| `resources/views/qna.blade.php` | Search, FAQ, support grouping | High | Medium |
| `resources/views/chat/show.blade.php` | Reduce redundant desktop framing | High | High |
| `resources/views/livewire/chat-thread.blade.php` | Bubble/composer visual alignment only | High | Very high |
| `resources/views/auth/*.blade.php` | Desktop auth rhythm, focus, secondary actions | High | Very high |
| `resources/views/admin/dashboard.blade.php` | Attention and priority hierarchy | Medium | Medium |
| `resources/views/livewire/admin/application-queue.blade.php` | Table status/action states | High | High |
| `resources/views/livewire/admin/document-queue.blade.php` | Document-specific table semantics | High | High |
| `resources/views/admin/applications/show.blade.php` | Section hierarchy and presentation ordering | High | Very high |
| `resources/views/livewire/admin/support-inbox.blade.php` | Unread/support accents | High | High |
| `resources/views/livewire/admin/user-directory.blade.php` | Identity markers and table interaction | High | High |
| `resources/views/livewire/admin/activity-feed.blade.php` | Event markers and chronology hierarchy | High | High |

No controller, provider, policy, route, migration, or database file should be needed.

---

## R. RESPONSIVE REGRESSION RISKS

### Phone ≤430 px

The strong mobile visual pass is implemented through dense late-file media rules. Broad base-selector changes could override:

- icon + label bottom navigation;
- compact header;
- focal dashboard/workspace/payment surfaces;
- document states;
- Admin mobile operational rows;
- chat sizing.

Desktop rules should generally be scoped with `min-width`.

### 431 px and intermediate widths

Do not assume 431 px immediately uses a complete desktop layout. Several intermediate breakpoints exist, including approximately 560, 600, 767/768, 820/821, 850/851, and 959/960.

A desktop-only rule starting at 431 px would unintentionally redesign tablets and narrow laptops.

### Public navigation 959/960

Public navigation changes must verify the actual switch at 959/960. Rules specific to the desktop header may begin at its established desktop boundary.

### Client navigation 850/851

Client top-navigation presentation changes can affect the 851–1023 intermediate range. If the task remains strictly desktop/laptop, styling should preferably begin at 1024 unless the existing navigation contract requires otherwise.

### Admin 1023/1024

This is a critical boundary:

- 1023 and below: drawer/mobile Admin presentation.
- 1024 and above: fixed desktop sidebar.

Desktop Admin visual rules should explicitly protect this switch.

### Shared selectors

High-risk classes include:

- icon display rules;
- status/badge bases;
- buttons;
- application record containers;
- chat surfaces;
- headers;
- focus-visible rules;
- generic `.surface` or card selectors.

### CSS cascade

Client/Public load `app.css` followed by `phase-b.css`. A shared rule added to `app.css` can be silently superseded by later Client CSS. Conversely, a broad `phase-b.css` rule can regress every Client mobile screen.

---

## S. ACCESSIBILITY CONSIDERATIONS

- Existing Client/Public link and button focus treatment uses a thin yellow outline that has weak contrast against white and pale backgrounds. This is a **P2 source-confirmed concern**.
- Use a 2 px visible focus ring with offset and sufficient contrast.
- Hover and focus must remain distinguishable.
- Icons accompanying labels can be decorative with `aria-hidden="true"`; icon-only actions require accessible names.
- Status must retain visible text and not become color-only.
- Avoid reducing metadata below practical readability.
- Table row hover cannot be the sole interaction cue.
- SVG icons should inherit color so high-contrast interaction states remain coherent.
- Selected filters require more than fill color alone: border/text/weight can reinforce selection.

This is redesign-relevant accessibility guidance, not a full WCAG certification.

---

## T. ANTI-SLOP FINDINGS

### Pattern: Equal-weight white card repetition

- **Where:** Client services/list/workspace; Admin dashboard/detail.
- **Why problematic:** Ownership, task priority, and supporting metadata appear equally important.
- **Direction:** Use one focal surface, neutral rows/dividers, and fewer boundaries.

### Pattern: Pill overuse risk

- **Where:** Status badges, filters, hero labels.
- **Why problematic:** Everything becomes visually “tag-like” without hierarchy.
- **Direction:** Compact radius, semantic marker, and text; retain pills only for genuine filter/status affordances.

### Pattern: Icon inconsistency

- **Where:** Admin sidebar exported SVG assets.
- **Why problematic:** Mixed geometry, weight, and hardcoded colors weaken product coherence.
- **Direction:** One functional current-color stroke family.

### Pattern: Color-without-function risk

- **Where:** Potential desktop adoption of the richer mobile palette.
- **Why problematic:** Turning every card into a different pastel would create decorative noise.
- **Direction:** Reserve every accent for a stable semantic meaning.

### Pattern: Generic SaaS dashboard risk

- **Where:** Admin dashboard metrics and Client service cards.
- **Why problematic:** Equal rounded metric cards can look templated.
- **Direction:** Let urgency, operational rows, and typography determine hierarchy.

### Pattern: Excessive visual chrome risk

- **Where:** Any future component combining icon container, badge, border, tint, shadow, and large radius.
- **Direction:** Choose the minimum visual mechanisms required to communicate ownership and state.

### Pattern: Mobile enlargement

- **Where:** Workspace, Admin queues, payment, and Help.
- **Why problematic:** Stretching mobile cards or single-column composition wastes desktop space.
- **Direction:** Share the component personality and semantics, not the layout.

---

## U. SOURCE CHANGE VERIFICATION

Baseline working tree already contained the latest mobile redesign changes and related untracked files.

Final working-tree status matches that baseline.

- Application source modified during this audit: **NO**
- Files created during this audit: **NO**
- Pre-existing changes reverted: **NO**
- `git diff --check`: **PASS**
- Build/tests: **NOT RUN**, because this was an audit-only task with no source changes.
- Rendered desktop widths: **NOT VERIFIED**
- Required future manual/browser QA: 1024, 1280, 1366, 1440, and 1920 px, plus 430/431, 850/851, 959/960, and 1023/1024 boundaries where affected.

---

## V. FINAL CLASSIFICATION

DESKTOP VISUAL HARMONIZATION AUDIT COMPLETE — READY FOR IMPLEMENTATION
