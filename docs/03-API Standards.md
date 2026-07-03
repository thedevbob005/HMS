# API Standards

Status: Baseline finalized  
Style: API-first JSON over HTTP

---

## 1. General Rules

- All protected endpoints require authentication.
- All hotel-scoped endpoints require hotel access checks.
- All writes require permission checks.
- All request bodies use JSON unless uploading files.
- All responses use the standard envelope.
- All validation errors must be field-specific where possible.

---

## 2. Response Envelope

Success:

```json
{
  "success": true,
  "data": {},
  "message": "Saved successfully"
}
```

Validation error:

```json
{
  "success": false,
  "message": "Please correct the highlighted fields.",
  "errors": {
    "guest_name": ["Guest name is required."]
  }
}
```

System error:

```json
{
  "success": false,
  "message": "Something went wrong. Please try again."
}
```

---

## 3. Status Codes

- `200` success.
- `201` created.
- `204` success without body.
- `400` bad request.
- `401` unauthenticated.
- `403` unauthorized.
- `404` not found.
- `409` business conflict.
- `422` validation failed.
- `429` rate limited.
- `500` server error.

Use `409` when a room was available earlier but is no longer available.

---

## 4. Route Style

Use REST-style names:

```text
GET    /api/hotels
POST   /api/hotels
GET    /api/hotels/{hotelId}/rooms
POST   /api/hotels/{hotelId}/reservations
POST   /api/hotels/{hotelId}/reservations/{reservationId}/check-in
POST   /api/hotels/{hotelId}/stays/{stayId}/check-out
POST   /api/hotels/{hotelId}/stays/{stayId}/room-shifts
GET    /api/hotels/{hotelId}/reports/occupancy
```

Use action routes for business events that are not simple CRUD, such as
`check-in`, `check-out`, `cancel`, `approve`, and `verify-aadhaar`.

---

## 5. Pagination

List endpoints should support:

- `page`
- `per_page`
- `search`
- `sort`
- `direction`

Default `per_page`: 25.  
Maximum `per_page`: 100 unless a report export explicitly allows more.

---

## 6. Filtering

Use query parameters for filters:

```text
GET /api/hotels/{hotelId}/reservations?status=confirmed&from=2026-07-01&to=2026-07-31
```

Dates should use ISO format:

`YYYY-MM-DD`

Date-times should use ISO 8601.

---

## 7. Idempotency

Payment collection, checkout, external verification, and messaging endpoints
should support idempotency keys once implementation reaches production hardening.

Recommended header:

`Idempotency-Key`

---

## 8. File Uploads

File uploads should use multipart form data.

Required checks:

- Authenticated user.
- Permission.
- Hotel access.
- File type.
- File size.
- Virus/malware scanning if available.
- Public-webroot upload folder with non-guessable paths, blocked directory
  listing, upload validation, and permission-checked metadata/API access.

Uploads include:

- Guest identity documents.
- Guest photos.
- Employee documents.
