# Bantu Daftarin Design Direction

Status: guidance for the upcoming UX redesign. This document does not implement or authorize UI, route, backend, view, or CSS changes by itself.

## Source and precedence

`DESIGN.md` is the source of direction for intentional, product-specific UX and visual choices. `ANTISLOP.md` is read after this file and acts only as a quality filter. It must not replace this direction with a generic style.

The product scope, lifecycle, security, and authorization rules in the master prompt and `docs/` remain binding. If a design choice conflicts with those rules or with an unresolved business decision, mark it `[BUSINESS CONFIRMATION REQUIRED]` instead of expanding the scope.

## Product identity

### What Bantu Daftarin is

Bantu Daftarin is a human-assisted administrative service for preparing NPWP applications for:

1. `NPWP Perseorangan`
2. `NPWP Badan Usaha`

The product helps a client understand the service, provide the required information and documents, pay, follow the review process, communicate with the operator, and receive the result through a private workspace.

The product is not an official government portal. The external NPWP process is handled manually by an admin outside the application. The UX must never imply DJP/Coretax integration, automatic registration, OCR, AI verification, biometric verification, or a `Lapor Pajak` workflow.

### Who it serves

- **Client**: a person or business representative who needs clear guidance through an administrative application and wants to know what is required, what has happened, and what to do next.
- **Super Admin**: the operator who manages the application queue, reviews documents, requests revisions, records estimates and external progress, verifies results, and completes the service.

The client experience should feel understandable to people who do not work with tax administration every day. The admin experience should support careful scanning, review, and auditable actions without turning the product into a generic analytics dashboard.

### Product character

The intended character is **clear, calm, practical, and human-assisted**. Bantu Daftarin should feel like a reliable service desk that guides a real administrative task, not like a government back office, a fintech checkout, or a fashionable developer SaaS product.

The emotional outcome is confidence through clarity: the user should be able to answer these questions at every important point:

- What service am I completing?
- What is the current status?
- What is the next action, and who needs to take it?
- Which information or document is still missing?
- What can I safely view, change, or download?

Use Indonesian product language that is direct, respectful, and plain. Prefer human labels such as `Perlu perbaikan dokumen` over internal state names such as `REVISION_REQUIRED`.

### Visual posture

Use a readable, high-contrast foundation with a deliberate brand accent and restrained semantic colors for status. Typography should prioritize legibility in Indonesian forms, requirements, payment details, and status history. A heading may carry brand character, but body text and controls must remain easy to scan. Do not use a technical monospace treatment unless the content is genuinely technical.

Visual energy should come from meaningful hierarchy, real application content, and purposeful feedback around state changes. Decoration must not compete with requirements, payment facts, private-document warnings, or the next action.

## UX principles

1. **Make the next action obvious.** Every active application view should establish the current state, the responsible party, and one primary next action. If no action is available, explain why and what the user can expect next.

2. **Guide completion in the order the work happens.** Service selection, data entry, requirement completion, payment, review, revision, external processing, and result delivery should form a comprehensible journey. Do not make users infer the workflow from isolated cards or decorative progress.

3. **Use honest, human-readable status.** Translate lifecycle states into labels users understand. Keep application status and payment status distinct. A browser return from a payment provider is not proof of payment; the UI must wait for the validated payment state.

4. **Treat requirements as a checklist, not a file dump.** Show what each document is for, whether it is required or conditional, its current version/status, and the precise reason for a revision when one exists. Errors should identify the field or document that needs attention.

5. **Make human assistance visible without pretending to automate.** Chat and operational messages should clarify who is acting and what is happening. Do not use automation language for work that an admin performs manually.

6. **Build trust from evidence.** Show real requirements, real prices, real status history, and real actions. Never add invented statistics, testimonials, service guarantees, security certifications, or processing promises. If information is not available, omit it or label it as an honest placeholder.

7. **Treat sensitive information as private by default.** Minimize exposure of personal and business data. Document and result access must remain authenticated, authorized, and private. A visual link, preview, or download affordance must not suggest that a sensitive file is public.

8. **Design all meaningful states.** Empty, loading, validation, revision, payment failure, expired payment, error, locked document, and completed states are part of the experience. Do not design only the successful upload and successful payment screenshots.

9. **Make actions accessible and reversible where appropriate.** Controls need clear focus, keyboard operation, readable contrast, useful error feedback, and confirmation for consequential actions. Do not hide a security boundary behind a visual control; the server remains authoritative.

## Navigation principles

### General rules

- Navigation represents real destinations and real capabilities in the current MVP. No dead links, placeholder pages presented as live, or navigation items for unbuilt features.
- The authenticated workspace is separate from public/authentication entry points. Once signed in, preserve the user's workspace context and current application context.
- Use friendly labels in the UI, while keeping route and state names an implementation concern.
- A hidden navigation item is not an authorization control. Policies and server-side checks remain mandatory.
- Do not introduce a new global navigation destination merely because a Figma frame, prototype link, or visual component exists.
- Keep the current location and the selected application clear. Back links and breadcrumbs should return users to a meaningful parent, not reset their progress.

### Client navigation

The client workspace should make these core destinations easy to reach:

- **Beranda**: the client's applications and the most important next actions, not fabricated metrics.
- **Aktivitas**: the history and current progress of the client's applications.
- **Layanan**: the two active NPWP services and their real requirements/price information.
- **Application context**: documents, payment, status history, chat, and verified results belong to the relevant application. They should not be presented as unrelated global tools when context is required.

`Lapor Pajak` may be shown as `Segera hadir` or `COMING_SOON` only when the product needs to communicate its future availability. It must not have an active form, payment, upload, workflow, price, requirement, or action that implies it can be ordered.

### Admin navigation

The admin workspace should prioritize:

- **Dashboard**: actionable operational overview based on real application data.
- **Antrian aplikasi**: applications that need review or action, with useful filtering based on actual lifecycle states.
- **Application detail**: documents, review/revision, estimate, external progress, result verification, completion, and audit-relevant context in one authorized workspace.

Do not add generic sections such as `Analytics`, `Team`, or `Reports` unless a real product requirement and destination exist. An admin action must correspond to a valid lifecycle transition and should not be represented as an arbitrary status dropdown.

## Application workspace concept

The **application** is the primary unit of work. The workspace is not a collection of unrelated pages; it is a persistent context for one NPWP service request from preparation through private result delivery.

### Client application workspace

Each application detail experience should organize information in this order of importance:

1. **Context**: service type and a safe, user-understandable application reference.
2. **Current status and next action**: a concise state explanation and the responsible actor.
3. **Progress**: a timeline or stepper that reflects the real lifecycle, including revision and waiting states.
4. **Requirements**: a checklist with document status, version/revision information, conditions, and upload or resubmission affordances only when allowed.
5. **Payment**: amount and payment status kept separate from the application lifecycle, with clear pending, failed, expired, or confirmed feedback.
6. **Conversation**: contextual chat or messages connected to the application.
7. **Result**: only the result that is authorized and verified for the client, with private view/download actions.

The workspace must respect document locking and revision rules. It must not offer replace/delete controls after payment or acceptance when the lifecycle disallows them. It must not imply that a blank or missing result means the external process failed; explain the actual state instead.

### Admin application workspace

The admin detail view should support a deliberate review sequence: establish application context, inspect requirements and document versions, record review decisions and reasons, manage valid transitions, record estimate/external progress, verify results, and complete or archive the application. The UI should expose relevant evidence and history without exposing unrelated clients or private resources.

## Responsive principles

- Design mobile-first for the client journey. A desktop frame is not permission to squeeze a multi-column form onto a phone.
- Preserve the order of work and the primary next action at every width. Responsive layout may stack, collapse, or regroup visual elements, but it must not change the underlying IA or hide essential context.
- Prevent horizontal overflow, clipped cards, escaping text, and collisions. Requirement names, revision reasons, payment details, and status labels must remain readable at narrow widths.
- Keep navigation comfortable on touch devices. Interactive targets should be at least 44px, with visible focus and pressed/active feedback.
- Use one clear column for forms, document upload, payment confirmation, and revision tasks on narrow screens. Keep supporting context close to the action it explains.
- Convert admin tables or dense desktop panels into readable stacked records or focused detail sections on small screens. Do not solve responsiveness by making users pan across sensitive or actionable information.
- Preserve loading, empty, error, locked, and completed states on every breakpoint. A responsive state is still a complete state.
- Use motion sparingly and only for orientation or feedback, such as confirming an upload or revealing a state change. Never make essential information depend on animation.

## Visual elements that may be reused from Figma

Figma may provide the visual language for the redesign. When visual reference is needed, use Figma MCP to inspect the verified Bantu Daftarin file and the specific route/node being worked on. Prefer existing, intentional components and tokens over inventing replacements.

The following may be reused when they exist in the verified Figma source and fit the current route:

- the approved wordmark, logo treatment, and brand marks;
- established color roles, typography, spacing, radius, border, shadow, and focus tokens;
- button, input, select, checkbox, alert, modal, and validation patterns;
- service selection and comparison patterns for the two active NPWP services;
- wizard/stepper, requirement checklist, document upload, revision, payment summary, status timeline, chat, result, queue, and review patterns;
- relevant icons, illustrations, and imagery that have a clear relationship to the product and are not assumed to be final without confirmation;
- composition, density, and visual hierarchy from the selected route, provided they remain accessible, responsive, and consistent with the real workflow.

Reuse means preserving a meaningful visual decision, not copying every visible object. Confirm that an asset belongs to Bantu Daftarin, is appropriate for the route, and does not reveal sensitive information. Do not invent a logo, avatar, testimonial portrait, statistic, or product screenshot when Figma does not provide an approved source.

## Elements of Figma that must not control information architecture

Figma is not the authority for product scope, business rules, authorization, or lifecycle. The following must not create or change IA:

- page names, frame names, canvas order, prototype links, or visual grouping;
- the number of cards, columns, sections, tabs, sidebars, or steps shown in a frame;
- decorative components, badges, illustrations, background treatments, or a component's existence without a real user task;
- placeholder copy, sample prices, sample metrics, fake testimonials, or labels that do not match current product data;
- a visual link to a route or feature that does not exist in the application;
- a future, duplicate, abandoned, or unverified screen;
- visual emphasis that conflicts with the actual lifecycle, payment truth, document permissions, or role authorization;
- a desktop-only composition that breaks the responsive principles above;
- any design that makes `Lapor Pajak`, DJP/Coretax access, automatic registration, OCR, AI verification, biometric checks, or other non-goals look active;
- any preview, download, or share pattern that turns a private document or result into a public resource.

When Figma and the product docs disagree, keep the documented scope, state machine, and security behavior. If the intended business behavior is unclear, write `[BUSINESS CONFIRMATION REQUIRED]` and stop the affected design decision there.

## Anti-generic UI direction

Bantu Daftarin should be recognizable as a calm, human-assisted administrative service even if its logo and name are removed. Its character should come from the real work: requirements, documents, payment truth, review decisions, revisions, human messages, external waiting, and private result delivery.

Prefer content-driven compositions:

- the service catalog helps users choose between real services and understand the commitment;
- the application workspace makes progress and the next action visible;
- the requirement view is a useful checklist rather than a repeated grid of identical feature cards;
- payment focuses attention on amount, current state, and what happens next;
- the admin workspace supports queue scanning and careful review rather than decorative KPI tiles.

Avoid generic SaaS or AI-generated patterns when they do not serve a documented purpose, including trend-stacked gradients/glows/glass effects, excessive pills, bento grids, uniform card mosaics, generic AI icons, fake terminal or product shots, fake statistics, fake testimonials, generic illustrations, default dark mode, and template sections such as an automatic three-step story or a filler FAQ. A technique is acceptable only when its purpose is clear, product-relevant, and compatible with accessibility and real behavior.

Do not replace generic UI with sterile UI. Create character through deliberate hierarchy, appropriate density, meaningful state feedback, plain Indonesian copy, and visual details that help the user complete this specific service. The final quality question is: if the brand mark and product name were swapped, would the experience still clearly express a real assisted NPWP workflow? If yes, the identity is probably coming from the product rather than decoration.

`DESIGN.md` supplies the direction. `ANTISLOP.md` checks whether the resulting work is intentional, functional, resilient, accessible, and honest. It does not get to redefine the Bantu Daftarin identity.

