# UX and Route Mapping

Client screens: auth, service catalog, application wizard/stepper, requirement checklist, payment, status timeline, chat, result list/download. Admin screens: login/2FA, dashboard, queue, application detail, document review/revision, estimate, external-status, result upload/verification, completion.

Terminologi internal seperti `REVISION_REQUIRED` diterjemahkan ke bahasa pengguna, misalnya “Perlu perbaikan dokumen”. Tombol tersembunyi bukan security control; server tetap memeriksa policy.

Figma mapping: `[BUSINESS CONFIRMATION REQUIRED]` bila file/node Figma belum tersedia di repository. Implementasi mengikuti security/business rules apabila visual reference bertentangan.
