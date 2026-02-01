# Bukku Inventory System

A RESTful API for tracking purchase and sale transactions with Weighted Average Cost (WAC) calculation.

## Features

- JWT Authentication (register, login, logout)
- Purchase and Sale transaction management
- Automatic WAC calculation
- Support for random date order transactions (auto-recalculates affected records)
- Update and delete transactions with automatic ledger recalculation

## Requirements

- Docker Desktop
- Make (optional, for convenience)

## Quick Setup

```bash
# Clone the repository
git clone <repository-url>
cd bukku-oscar

# Run complete setup (installs deps, starts containers, migrates, seeds)
make setup
```

App will be running at `http://localhost`

API Documentation available at `http://localhost/docs/api`

## Manual Setup

```bash
# 1. Copy environment file
cp .env.example .env

# 2. Install dependencies (before Sail is available)
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php85-composer:latest \
    composer install --ignore-platform-reqs

# 3. Build and start Sail containers
./vendor/bin/sail build
./vendor/bin/sail up -d

# 4. Generate JWT secret
./vendor/bin/sail artisan jwt:secret

# 5. Run migrations and seeders
./vendor/bin/sail artisan migrate --seed
```

## Available Make Commands

| Command | Description |
|---------|-------------|
| `make setup` | Complete project setup |
| `make build` | Build Sail containers |
| `make up` | Start Sail containers |
| `make down` | Stop Sail containers |
| `make install` | Install composer dependencies (via Sail) |
| `make migrate` | Run database migrations |
| `make seed` | Run database seeders |
| `make fresh` | Fresh migration with seeders |
| `make test` | Run tests |
| `make run-test` | Run tests (alias) |
| `make clean` | Clear all caches |
| `make routes` | Show API routes |
| `make shell` | Open shell in container |
| `make logs` | View container logs |
| `make jwt-secret` | Generate JWT secret key |
| `make ide-helper` | Regenerate IDE helper annotations |

## API Endpoints

### Authentication

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/auth/register` | Register new user |
| POST | `/api/auth/login` | Login |
| POST | `/api/auth/logout` | Logout (requires token) |
| POST | `/api/auth/refresh` | Refresh token |
| GET | `/api/auth/me` | Get current user |

### Transactions

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/transactions` | List transactions |
| POST | `/api/transactions` | Create transaction |
| PUT | `/api/transactions/{id}` | Update transaction |
| DELETE | `/api/transactions/{id}` | Delete transaction |

#### Query Parameters for GET /api/transactions

| Param | Description |
|-------|-------------|
| `type` | Filter by `purchase` or `sale` |
| `product_id` | Filter by product ID |

## API Examples

### Register
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password",
    "password_confirmation": "password"
  }'
```

### Login
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "password"
  }'
```

### Create Purchase
```bash
curl -X POST http://localhost:8000/api/transactions \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "product_id": 1,
    "transaction_type": "purchase",
    "transaction_date": "2022-01-01",
    "quantity": 150,
    "unit_price": 2.00
  }'
```

### Create Sale
```bash
curl -X POST http://localhost:8000/api/transactions \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "product_id": 1,
    "transaction_type": "sale",
    "transaction_date": "2022-01-05",
    "quantity": 50,
    "unit_price": 3.00
  }'
```

### List Transactions
```bash
# All transactions
curl -X GET http://localhost:8000/api/transactions \
  -H "Authorization: Bearer YOUR_TOKEN"

# Filter by type
curl -X GET "http://localhost:8000/api/transactions?type=sale" \
  -H "Authorization: Bearer YOUR_TOKEN"

# Filter by product
curl -X GET "http://localhost:8000/api/transactions?product_id=1" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Update Transaction
```bash
curl -X PUT http://localhost:8000/api/transactions/1 \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "quantity": 200
  }'
```

### Delete Transaction
```bash
curl -X DELETE http://localhost:8000/api/transactions/1 \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## Project Structure

```
app/
├── Constants/              # Application constants
├── DataTransferObjects/    # DTOs for type-safe data passing
├── Exceptions/             # Custom exceptions
├── Http/
│   ├── Controllers/        # API controllers
│   ├── Middleware/         # Custom middleware
│   └── Requests/           # Form request validation
├── Models/                 # Eloquent models
├── Repositories/           # Data access layer
├── Services/               # Business logic services
│   ├── InventoryLedger/    # WAC calculation & ledger services
│   └── Transactions/       # Transaction CRUD & rules
└── UseCases/               # Application use cases
    └── Transactions/       # Create, Update, Delete use cases
```

## WAC (Weighted Average Cost) Calculation

The system uses Weighted Average Cost method:

1. **Purchase**: Adds to inventory, recalculates average cost
   - New Total Value = Old Total Value + (Quantity × Unit Price)
   - New Average Cost = New Total Value / New Quantity

2. **Sale**: Reduces inventory using current average cost
   - Cost of Goods Sold = Quantity × Average Cost
   - New Total Value = Old Total Value - COGS

## Testing

Run the test suite:

```bash
make test
# or
./vendor/bin/sail artisan test
```

### Test Coverage

| Test Suite | Tests | Description |
|------------|-------|-------------|
| **Unit Tests** | | |
| `CalculateWacServiceTest` | 11 | Pure WAC calculation logic |
| `RecalculateLedgerServiceTest` | 4 | Service orchestration with mocked repositories |

## Seeded Data

The seeder creates 5 sample products:
- Widget A (WGT-001)
- Widget B (WGT-002)
- Gadget X (GDT-001)
- Gadget Y (GDT-002)
- Component Z (CMP-001)

And a test user:
- Email: test@example.com
- Password: password

## License

MIT
