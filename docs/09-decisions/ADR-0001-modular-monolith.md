# ADR-0001: Modular Monolith Laravel

## Status

Accepted for MVP.

## Decision

Gunakan satu aplikasi Laravel modular dengan route/controller boundary Client, Admin, dan Webhook; domain actions/services; Eloquent; private storage; database queue.

## Rationale

Scope MVP membutuhkan workflow kuat, auditability, dan security tanpa kompleksitas microservices, API gateway, Redis, WebSocket, atau separate frontend/backend.

## Consequences

Boundary kode dan policy harus disiplin. Integrasi provider dapat diganti lewat contract/service tanpa memecah deployment.
