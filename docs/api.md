# EventPro API Documentation

Base URL: `https://your-domain.com/api/v1`

Authentication: Bearer token via Laravel Sanctum (`Authorization: Bearer {token}`)

---

## Public Endpoints

### Venues

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/venues` | List active venues (paginated) |
| GET | `/venues/{id}` | Get venue details |
| GET | `/venues/{id}/availability` | Check venue availability for a date |

**GET /venues** — Query params: `search`, `capacity`, `per_page`

Response:
```json
{
  "data": [
    {
      "id": 1,
      "name": "The Grand Ballroom",
      "slug": "the-grand-ballroom",
      "description": "...",
      "capacity_min": 100,
      "capacity_max": 500,
      "base_price": "8000.00",
      "weekend_surcharge": "1500.00",
      "amenities": ["parking", "valet", "catering"],
      "status": "active"
    }
  ],
  "meta": { "current_page": 1, "total": 3, "per_page": 15 }
}
```

### Packages

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/packages` | List active packages |
| GET | `/packages/{id}` | Get package details |

### Pricing

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/calculate-price` | Calculate event pricing |

**POST /calculate-price** — Body:
```json
{
  "venue_id": 1,
  "package_id": 2,
  "guest_count": 150,
  "event_date": "2025-06-15",
  "services": [
    { "name": "Photography", "pricing_type": "flat", "unit_price": 1200, "quantity": 1 }
  ],
  "discount": { "type": "percentage", "value": 10 }
}
```

### Inquiries

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/inquiries` | Submit a new inquiry |

---

## Authenticated Endpoints

All authenticated endpoints require `Authorization: Bearer {token}`.

### Client Portal

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/client/bookings` | List client's bookings |
| GET | `/client/bookings/{id}` | Booking details |
| GET | `/client/quotations` | List client's quotations |
| GET | `/client/quotations/{id}` | Quotation details |
| POST | `/client/quotations/{id}/accept` | Accept a quotation |

---

## Admin Endpoints

All admin endpoints require authentication + `admin` or `manager` role.

### Venues

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/venues` | List all venues |
| POST | `/admin/venues` | Create venue |
| GET | `/admin/venues/{id}` | Get venue |
| PUT | `/admin/venues/{id}` | Update venue |
| DELETE | `/admin/venues/{id}` | Delete venue |

**POST /admin/venues** — Required fields: `name`, `capacity_min`, `capacity_max`, `base_price`

### Packages

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/packages` | List packages |
| POST | `/admin/packages` | Create package |
| GET | `/admin/packages/{id}` | Get package |
| PUT | `/admin/packages/{id}` | Update package |
| DELETE | `/admin/packages/{id}` | Delete package |

### Clients

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/clients` | List clients (paginated) |
| POST | `/admin/clients` | Create client |
| GET | `/admin/clients/{id}` | Get client |
| PUT | `/admin/clients/{id}` | Update client |
| DELETE | `/admin/clients/{id}` | Delete client |

### Inquiries

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/inquiries` | List inquiries |
| POST | `/admin/inquiries` | Create inquiry |
| GET | `/admin/inquiries/{id}` | Get inquiry |
| PUT | `/admin/inquiries/{id}` | Update inquiry |
| DELETE | `/admin/inquiries/{id}` | Delete inquiry |

### Bookings

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/bookings` | List bookings |
| POST | `/admin/bookings` | Create booking |
| GET | `/admin/bookings/{id}` | Get booking |
| PUT | `/admin/bookings/{id}` | Update booking |
| DELETE | `/admin/bookings/{id}` | Delete booking |

### Quotations

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/quotations` | List quotations |
| POST | `/admin/quotations` | Create quotation |
| GET | `/admin/quotations/{id}` | Get quotation |
| PUT | `/admin/quotations/{id}` | Update quotation |
| POST | `/admin/quotations/{id}/send` | Mark quotation as sent |
| GET | `/admin/quotations/{id}/pdf` | Download PDF |

### Payments

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/payments` | List payments |
| POST | `/admin/payments` | Record payment |
| GET | `/admin/payments/{id}` | Get payment |
| PUT | `/admin/payments/{id}` | Update payment |

### Vendors

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/vendors` | List vendors |
| POST | `/admin/vendors` | Create vendor |
| GET | `/admin/vendors/{id}` | Get vendor |
| PUT | `/admin/vendors/{id}` | Update vendor |
| DELETE | `/admin/vendors/{id}` | Delete vendor |

### Staff

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/staff` | List staff |
| POST | `/admin/staff` | Create staff member |
| GET | `/admin/staff/{id}` | Get staff member |
| PUT | `/admin/staff/{id}` | Update staff member |
| DELETE | `/admin/staff/{id}` | Delete staff member |
| GET | `/admin/staff/{id}/schedule` | Get staff schedule/tasks |

### Tasks

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/tasks` | List tasks |
| POST | `/admin/tasks` | Create task |
| GET | `/admin/tasks/{id}` | Get task |
| PUT | `/admin/tasks/{id}` | Update task |
| DELETE | `/admin/tasks/{id}` | Delete task |

### Reports

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/reports/revenue` | Monthly revenue breakdown |
| GET | `/admin/reports/bookings` | Bookings by month/type |
| GET | `/admin/reports/inquiries` | Inquiry stats |

### Settings

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/settings` | Get tenant settings |
| PUT | `/admin/settings` | Update settings |

### Custom Fields

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/custom-fields` | List custom fields |
| POST | `/admin/custom-fields` | Create custom field |
| PUT | `/admin/custom-fields/{id}` | Update custom field |
| DELETE | `/admin/custom-fields/{id}` | Delete custom field |

---

## Error Responses

| Status | Meaning |
|--------|---------|
| 200 | Success |
| 201 | Created |
| 400 | Bad Request |
| 401 | Unauthenticated |
| 403 | Forbidden (insufficient role) |
| 404 | Not Found |
| 422 | Validation Error |
| 429 | Too Many Requests (rate limited) |
| 500 | Server Error |

**Validation Error (422):**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "name": ["The name field is required."],
    "event_date": ["The event date must be a future date."]
  }
}
```

**Rate Limits:**
- Public endpoints: 30 req/min per IP
- Authenticated endpoints: 120 req/min per user
- Login: 5 attempts/min per IP
- Inquiry form: 3 submissions/min per IP
