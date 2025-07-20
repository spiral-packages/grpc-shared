# Generator

## 🧭 Purpose of the Component

The Generator component is a powerful PHP code generation tool that automatically creates client-side code from Protocol
Buffer (protobuf) service definitions. It transforms gRPC service interfaces into fully functional PHP classes,
commands, handlers, mappers, and configuration files. This component eliminates the need for manual boilerplate code
creation and ensures consistency across all generated services.

## 🧍 List of Actors

### System Actors

- **Proto Files** - Source definitions containing service interfaces and message structures
- **gRPC Services** - Remote services that the generated code will communicate with
- **PHP Application** - The host application that uses the generated service clients
- **Protoc Compiler** - The underlying Protocol Buffer compiler that processes .proto files
- **File System** - Stores generated PHP classes and configuration files

### 📚 Domain Ubiquitous Terminology

- **Generator Command** - The main CLI command that orchestrates the entire code generation process
- **Service Client** - Generated PHP class that provides methods to call remote gRPC service methods
- **Command Class** - Generated data transfer objects that represent request/response messages
- **Handler Class** - Generated classes that process commands and delegate to service clients
- **Mapper Class** - Generated classes that transform between protobuf messages and PHP objects
- **Bootloader** - Generated configuration class that sets up service dependencies and connections
- **Protoc Binary** - The Protocol Buffer compiler executable used to process .proto files
- **Message Fixer** - Post-processing component that enhances generated protobuf message classes
- **Annotations Parser** - Component that extracts metadata from protobuf comments and converts to PHP attributes

## 🚀 Quick Start Workflow

Before starting, ensure you have:

- PHP 8.3 or higher
- Composer installed
- Access to your proto files repository

### 1.1 Create Proto Files Repository

Create a dedicated Composer package containing only your proto files:

```
proto-files-package/
├── composer.json
├── README.md
└── proto/
    ├── user/
    │   └── v1/
    │       ├── user_service.proto
    │       └── user_messages.proto
    ├── payment/
    │   └── v1/
    │       ├── payment_service.proto
    │       └── payment_messages.proto
    └── shared/
        └── common.proto
```

### 1.2 Configure composer.json for Proto Package

```json
{
  "name": "your-company/proto-files"
  //...
}
```

### 1.3 Version Your Proto Files

Use semantic versioning for your proto files:

- **Major version** (2.0.0): Breaking changes to service interfaces
- **Minor version** (1.1.0): New services or non-breaking additions
- **Patch version** (1.0.1): Bug fixes or documentation updates

### 2.1 Add Proto Files Dependency

In your main project's `composer.json`:

```json
{
  "require": {
    "your-company/proto-files": "^1.0",
    "spiral/framework": "^3.0",
    "grpc/grpc": "^1.50"
  },
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/your-company/proto-files"
    }
  ]
}
```

### 2.2 Install Dependencies

```bash
composer install
```

### 2.3 Download Proto Generator Binary

```bash
composer download
```

### 3.1 Update Proto Files Package

When proto files change in your service definitions:

```bash
# In your proto-files repository
git pull origin main
./scripts/release.sh  # Run your release script
# Enter new version when prompted (e.g., v1.2.0)
```

### 3.2 Update Your Main Project

```bash
# In your main project
composer update your-company/proto-files
```

### 3.3 Check Updated Dependencies

```bash
composer show your-company/proto-files
# Should show the new version number
```

### 4.1 Execute Generator

```bash
# Basic generation
php console.php generate --proto vendor/your-company/proto-files/proto

# With verbose output to see detailed progress
php console.php generate  --proto vendor/your-company/proto-files/proto -v

# Example output:
# Compiling `user/v1`:
# • src/Services/User/v1/UserServiceInterface.php
# • src/Services/User/v1/CreateUserRequest.php
# • src/Services/User/v1/GetUserResponse.php
# Running `Generator\Generators\ConfigGenerator`:
# Running `Generator\Generators\ServiceClientGenerator`:
# Running `Generator\Generators\CommandClassGenerator`:
# Done!
```

### 4.2 Verify Generated Files

Check that files were created in the expected locations:

```
src/
├── Services/
│   ├── User/v1/
│   │   ├── UserServiceClient.php
│   │   ├── UserServiceInterface.php
│   │   └── Messages/...
│   └── Payment/v1/
│       ├── PaymentServiceClient.php
│       └── PaymentServiceInterface.php
├── Command/
│   ├── User/v1/
│   │   ├── CreateUserCommand.php
│   │   └── GetUserCommand.php
│   └── Payment/v1/
├── Handler/
├── Mapper/
├── Bootloader/
│   └── ServiceBootloader.php
└── Config/
    └── GRPCServicesConfig.php
```

### 5.1 Review Generated Files

```bash
# See what files changed
git status

# Review the changes
git diff --name-only
git diff src/Command/
git diff src/Services/
```

### 5.2 Commit with Descriptive Messages

```bash
# Stage generated files
git add src/

# Commit with clear message linking to proto version
git commit -m "Generate service clients for proto-files v1.2.0

- Added new CreateUserV2 command with validation
- Updated PaymentService with refund support
- Regenerated all handlers and mappers

Proto files version: your-company/proto-files@v1.2.0"

# Push changes
git push origin main
```

### 6.1 Configure Service Endpoints

After generation, configure your environment variables:

```bash
# .env.local
USER_SERVICE_CLIENT_HOST=user-service.internal:9000
PAYMENT_SERVICE_CLIENT_HOST=payment-service.internal:9001

# For development
USER_SERVICE_CLIENT_HOST=localhost:9000
PAYMENT_SERVICE_CLIENT_HOST=localhost:9001
```

### 6.2 Update Docker Compose (if applicable)

```yaml
# docker-compose.yml
services:
  app:
    environment:
      - USER_SERVICE_CLIENT_HOST=user-service:9000
      - PAYMENT_SERVICE_CLIENT_HOST=payment-service:9001
    depends_on:
      - user-service
      - payment-service

  user-service:
    image: your-company/user-service:latest
    ports:
      - "9000:9000"

  payment-service:
    image: your-company/payment-service:latest
    ports:
      - "9001:9001"
```

### 7.1 Basic Usage Example

```php
<?php

use Internal\Shared\gRPC\Services\User\v1\UserServiceClient;
use Internal\Shared\gRPC\Command\User\v1\CreateUserCommand;
use Internal\Shared\gRPC\RequestContext;

class UserController
{
    public function __construct(
        private UserServiceClient $userService,
        private RequestContext $context
    ) {}
    
    public function createUser(array $data): UserResponse
    {
        $command = new CreateUserCommand(
            email: $data['email'],
            name: $data['name'],
            age: $data['age'] ?? null
        );
        
        return $this->userService->createUser($this->context, $command);
    }
}
```

### 7.2 Using with Command Handlers

```php
<?php

use Spiral\Cqrs\CommandBusInterface;
use Internal\Shared\gRPC\Command\User\v1\CreateUserCommand;

class UserRegistrationService
{
    public function __construct(
        private CommandBusInterface $commandBus
    ) {}
    
    public function registerUser(array $userData): void
    {
        $command = new CreateUserCommand(
            email: $userData['email'],
            name: $userData['name']
        );
        
        // Command handler will automatically use generated handler
        $result = $this->commandBus->handle($command);
    }
}
```

## 🔧 How It Works - Detailed Technical Process

### Phase 1: Initialization and Setup

The Generator component begins execution when the `GeneratorCommand` is invoked through the console interface:

```php
php console.php generate
```

**Step 1.1: Binary Verification**

- The system checks for the existence of `protoc-gen-php-grpc` binary in the root directory
- This binary is essential for generating PHP gRPC client code from proto files
- If missing, the process terminates with instructions to run `composer download`

**Step 1.2: Directory Discovery**

- The command scans configured proto file directories (`$protoFileDirs`)
- Each directory is validated to ensure it exists and contains `.proto` files
- Non-existent directories are logged as errors but don't stop the process

**Step 1.3: Component Initialization**

- Creates instances of all generator components:
    - `ProtoCompiler` - Handles protoc command execution
    - `ProtocCommandBuilder` - Builds protoc commands with proper flags
    - `CommandExecutor` - Executes shell commands safely
    - Array of `GeneratorInterface` implementations for different output types

### Phase 2: Proto File Compilation

**Step 2.1: Command Building**
The `ProtocCommandBuilder` constructs a protoc command with specific parameters:

```bash
protoc --plugin=/path/to/protoc-gen-php-grpc \
       --php_out=/tmp/generated \
       --php-grpc_out=/tmp/generated \
       -I=/vendor/proto-files \
       -I=/current/proto/dir \
       service.proto message.proto
```

**Step 2.2: File Filtering**

- Only files ending with `.proto` are included
- Google's standard proto files are excluded to avoid conflicts
- Each proto directory is processed independently

**Step 2.3: Temporary Directory Management**

- Creates unique temporary directory using `spl_object_hash($this)`
- Executes protoc command with output directed to temp directory
- Captures both stdout and stderr for error handling

**Step 2.4: Error Handling**

- Monitors protoc exit codes (0 = success, non-zero = error)
- Throws `CompileException` with detailed error output if compilation fails
- Cleans up temporary directories even on failure

**Step 2.5: File Relocation**

- Moves generated files from temp directory to final destination
- Preserves namespace-based directory structure
- Removes temporary files and directories

### Phase 3: PHP Class Generation Pipeline

The Generator runs multiple specialized generators in a specific order to ensure dependencies are properly resolved:

**Step 3.1: Config Generation (`ConfigGenerator`)**

- Creates `GRPCServicesConfig.php` if it doesn't exist
- Defines configuration structure for service connections
- Sets up default credential handling and interceptor configuration
- Only runs once to avoid overwriting custom configurations

**Step 3.2: Service Client Generation (`ServiceClientGenerator`)**
For each `*Interface.php` file found:

- Parses the interface to extract method signatures
- Creates corresponding `*ServiceClient.php` class
- Implements the original interface
- Adds `ServiceClientTrait` for common functionality
- Generates method bodies that delegate to `callAction()`
- Creates test files with mock data for each service method

**Step 3.3: Message Processing (`GeneratedMessagesFixer`)**

- Scans all generated protobuf message classes
- Parses docblock comments for annotations
- Converts `@Event` annotations to PHP attributes
- Implements `ProtoEvent` interface where applicable
- Fixes comment formatting and adds proper PHPDoc

**Step 3.4: Command Class Generation (`CommandClassGenerator`)**
This is the most complex phase, involving multiple sub-processes:

**Step 3.4a: Message Parsing**

- Uses `MessageClassParser` to analyze protobuf message classes
- Extracts property information using reflection
- Parses docblock comments to understand field types and constraints
- Creates `PropertyType` objects with full metadata

**Step 3.4b: Command Class Creation**

- Generates DTO classes in the `Command` namespace
- Makes classes `final` and `readonly` for immutability
- Implements `CommandInterface` for request messages
- Adds `JsonSerializable` interface with proper serialization logic

**Step 3.4c: Property Generation**

- Creates constructor with promoted parameters
- Applies proper PHP type hints based on protobuf types
- Handles optional fields with default values
- Converts protobuf enums to PHP enum classes
- Adds validation attributes from protobuf annotations

**Step 3.4d: Handler Generation**

- Creates handler classes for each service method
- Generates `__invoke` methods with proper signatures
- Adds `CommandHandler` attribute for CQRS integration
- Implements delegation to service clients with context passing

**Step 3.4e: Mapper Generation**

- Creates mapper classes extending `AbstractMapper`
- Implements `fromMessage()` method for protobuf to DTO conversion
- Uses Valinor for type-safe object creation
- Handles complex type transformations (timestamps, enums, arrays)

**Step 3.5: Enum Class Generation (`EnumClassGenerator`)**

- Extracts enum definitions from protobuf descriptors
- Creates PHP 8.1+ backed enum classes with integer values
- Handles enum case naming conventions
- Returns default enum value for property initialization

**Step 3.6: Service Interface Enhancement (`ServiceInterfaceAttributesGenerator`)**

- Adds PHP attributes to existing interface methods
- Processes annotations like `@Guarded` and `@Internal`
- Adds proper parameter documentation for `RequestContext`
- Maintains original interface structure while adding metadata

**Step 3.7: Bootloader Generation (`BootloaderGenerator`)**

- Creates or updates `ServiceBootloader.php`
- Generates service configuration with environment variable mapping
- Creates service binding logic for dependency injection
- Adds interceptor chain configuration
- Handles incremental updates without losing custom code

**Step 3.8: Service Provider Generation (`ServiceProviderGenerator`)**

- Creates Laravel-compatible service provider
- Generates singleton bindings for service clients
- Configures gRPC client cores with proper credentials
- Sets up exception mapping and interceptor chains

**Step 3.9: Environment Template Generation (`EnvTemplateGenerator`)**

- Outputs environment variable templates to console
- Creates variables like `USER_SERVICE_CLIENT_HOST=`
- Uses service names to generate consistent variable names
- Provides copy-paste ready configuration templates

### Phase 4: Type System and Transformation

**Step 4.1: Type Factory Processing**
The `TypeFactory` handles complex type transformations:

- Converts protobuf types to PHP types (e.g., `Timestamp` → `DateTimeInterface`)
- Handles repeated fields as PHP arrays
- Processes nested message types as class references
- Manages special Google types (`Any`, `FieldMask`, `Duration`)

**Step 4.2: Property Type Analysis**
Each field goes through comprehensive analysis:

- Determines if field is optional, required, or repeated
- Extracts default values from annotations
- Applies validation constraints from protobuf options
- Handles enum types with proper case conversion

**Step 4.3: Class Transformation**
The `ClassTransformer` manages namespace operations:

- Converts between different namespace contexts
- Handles path generation for file output
- Manages class name transformations (Interface → Client)
- Ensures PSR-4 compliance

### Phase 5: Output Generation and File Management

**Step 5.1: File Declaration System**
Uses Spiral's Reactor library for PHP code generation:

- Creates AST-like structures for classes
- Manages imports and namespace declarations
- Handles method generation with proper signatures
- Generates formatted PHP code with proper indentation

**Step 5.2: Persistence Strategy**

- Checks if files exist before overwriting
- Preserves custom modifications where possible
- Uses special markers for generated sections
- Maintains file permissions and structure

**Step 5.3: Error Recovery**

- Continues processing even if individual files fail
- Logs specific errors with file names and reasons
- Provides detailed output in verbose mode
- Cleans up partial outputs on critical failures

### Phase 6: Integration and Validation

**Step 6.1: Cross-Reference Resolution**

- Links command classes to their corresponding handlers
- Connects service clients to their interfaces
- Resolves mapper dependencies
- Updates bootloader with new service registrations

**Step 6.2: Dependency Injection Setup**

- Configures service container bindings
- Sets up interceptor chains
- Handles credential management
- Creates proper service scoping (singleton vs transient)

**Step 6.3: Final Validation**

- Verifies all generated files are syntactically valid
- Checks class loading and autoloading compatibility
- Validates that all required dependencies are available
- Reports generation summary with file counts and any issues

This entire process typically completes in seconds for small to medium service definitions, but can take longer for
large proto files with many services and complex message types.

## 🧪 Simple Use Cases

### Use Case 1: Adding New Service Integration

**User**: Developer wants to integrate with a new user management service
**System Actions**:

1. Developer places UserService.proto file in the proto directory
2. Developer runs `php console.php generate` command
3. Generator reads the proto file and creates UserServiceClient.php
4. Generator creates command classes for CreateUser, GetUser, UpdateUser requests
5. Generator creates corresponding handler classes and mappers
6. Developer can immediately use `$userClient->createUser($command)` in their code

### Use Case 2: Updating Existing Service

**User**: Business analyst needs to understand what happens when a service definition changes
**System Actions**:

1. Backend team updates the UserService.proto to add new fields
2. Generator re-runs and updates all related PHP classes automatically
3. New fields appear in command classes with proper validation
4. Existing code continues to work due to backward compatibility
5. New features can immediately use the additional fields

### Use Case 3: Environment Configuration

**User**: DevOps engineer deploying to different environments (dev, staging, production)
**System Actions**:

1. Generator creates environment variable templates like `USER_SERVICE_CLIENT_HOST=`
2. DevOps sets appropriate values: dev=localhost:9000, staging=user-service.staging:9000
3. Generated service clients automatically connect to correct endpoints
4. No code changes needed between environments
