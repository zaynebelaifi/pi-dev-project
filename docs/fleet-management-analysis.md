# Fleet Management Analysis (Symfony 6.x)

## 1) Existing Architecture Findings

### Existing entities relevant to fleet
- `User` (`user`): `id`, `email`, `password_hash`, `role`, `first_name`, `last_name`, `banned`, `phone`, `phone_number`, `address`.
- `DeliveryMan` (`delivery_man`): `delivery_man_id`, `name`, `phone`, `email`, `vehicle_type`, `vehicle_number`, `status`, `address`, `salary`, `date_of_joining`, `rating`, `created_at`, `updated_at`, `latitude`, `longitude`, `last_location_update`.
- `FleetCar` (`fleet_car`): `car_id`, `make`, `model`, `license_plate`, `vehicle_type`, `delivery_man_id` (scalar integer).
- `Delivery` (`delivery`): links to `DeliveryMan` and optional `FleetCar`, includes destination GPS fields.

### Existing fleet logic
- Service `FleetService` already provides:
  - Haversine distance.
  - Delivery-man location update.
  - Nearest-driver assignment for deliveries.
  - Simple fleet optimization suggestions.
- API controller `FleetController` currently exposes custom endpoints under `/api/fleet/*`.
- Fleet map UI exists in Twig (`templates/fleet/dashboard.html.twig`) using Leaflet and polling via fetch.

### Existing role model
- Effective roles in app: `ROLE_ADMIN`, `ROLE_DELIVERY_MAN`, `ROLE_CLIENT` and `ROLE_USER` in computed roles.
- `security.yaml` currently protects:
  - `/api/login` as public.
  - `/api/driver/*` for delivery role.
  - generic `/api` requires `ROLE_USER`.
- A large part of admin authorization is still done through legacy session key checks (`session.user_role`) in controllers.

### Existing route pattern
- Mixed patterns:
  - Admin HTML routes under `/admin/*`.
  - Existing API routes under `/api/*` (custom + REST-like mix).
  - Fleet API currently under `/api/fleet/*` custom endpoints.

### Frontend stack and design language
- Twig SSR templates.
- Bootstrap CSS loaded globally + substantial custom CSS in `assets/styles/app.css` and inline template CSS.
- Primary design tokens revolve around ivory/gold/espresso palette.
- JavaScript is mainly inline script blocks inside Twig templates plus a few `public/js/*` files.
- Current map integration uses Leaflet in Twig.

## 2) Integration Points Identified

1. Keep `FleetCar` as the core car entity to avoid breaking existing links and routes.
2. Add missing fleet-management capabilities by extending `FleetCar` and `DeliveryMan`, not replacing them.
3. Add new entities around existing model:
   - `GPSLog`
   - `AssignmentHistory`
   - `AuditLog`
   - `Notification`
4. Preserve existing `FleetController` endpoints for backward compatibility; add full REST endpoints under new controllers (`/api/cars`, `/api/assignments`, `/api/gps`, etc.).
5. Keep existing admin UI style by extending `backend.html.twig` and using same color tokens/components.
6. Keep security compatible with both:
   - Symfony role checks (`isGranted`)
   - legacy session role checks where existing code still relies on it.

## 3) Gaps vs Requested Target

- Missing entities: `GPSLog`, `AssignmentHistory`, `AuditLog`, `Notification`.
- Missing standardized fleet REST API set.
- Missing robust assignment transaction/locking flow.
- Missing unified error response contract for fleet APIs.
- `FleetCar` currently lacks many operational fields (status, fuel/battery, GPS stamp, etc.).
- `DeliveryMan` lacks explicit assignment relation (`currentCar`) and availability metrics.
- Missing dedicated services split (`CarService`, `AssignmentService`, `GPSService`, `AuditService`, `NotificationService`, `DistanceCalculator`).

## 4) Non-breaking Implementation Strategy

1. Extend entities with backward-compatible nullable fields first.
2. Add migration with additive changes only.
3. Add new services and APIs while keeping existing `/api/fleet/*` untouched.
4. Add admin pages incrementally and link them from existing backend navigation.
5. Add targeted tests for service-level logic first (distance, assignment guards, GPS validation).

## 5) Implementation Phase Plan (repo-compatible)

- Phase A (Core data + services):
  - Extend `FleetCar`, `DeliveryMan`, `User`.
  - Create new fleet entities and repositories.
  - Add migration.
  - Implement `DistanceCalculator`, `CarService`, `AssignmentService`, `GPSService`, `AuditService`, `NotificationService`.

- Phase B (API):
  - Add controllers for `/api/cars`, `/api/assignments`, `/api/gps`, `/api/notifications`.
  - Apply access-control rules.
  - Unified JSON error format.

- Phase C (Admin UI):
  - Fleet dashboard stats + map data API integration.
  - Cars list/details/forms.
  - Assignment management page.

- Phase D (Ops):
  - Add inactivity check command.
  - Add targeted unit tests.
