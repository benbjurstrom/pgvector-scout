# PgVector Scout Package for Laravel

This package provides a Laravel Scout engine for the pgvector PostgreSQL extension. Laravel Scout is a first-party package for Laravel that is used for searching models using vector embeddings.

## Architecture Overview

Scout is an excellent choice for this implementation since it automatically uses model observers to keep the searchable data up to date. While traditionally Scout engines store data in separate search systems like Typesense or Meilisearch, this package leverages PostgreSQL's pgvector extension to store and search vector embeddings directly in the database.

### Key Components

1. **Configurable Vector Storage**
   - Vectors and metadata can be stored in configurable tables per handler
   - Each handler can specify its own table name and vector dimensions
   - Uses Laravel's polymorphic relationships to associate vectors with models
   - Schema defined dynamically based on handler configuration

2. **Handler System**
   - Configurable handlers for different embedding providers (e.g., OpenAI)
   - Each handler can specify its own:
     - Table name
     - Vector dimensions
     - Model/API configuration
     - API endpoints and authentication
   - Easy to extend with custom handlers

3. **Embedding Model**
   - The package provides an `Embedding` model for managing vectors
   - Dynamically uses the correct table based on the default handler
   - Creates polymorphic relationships to searchable models
   - Stores vector data, content hash, and embedding model information

4. **Vector Updates**
   - Implements efficient vector updates using content hashing
   - Only generates new embeddings when content changes
   - Supports Laravel Scout's queueing system for async vector generation
   - Integrates with external embedding services (e.g., OpenAI)

5. **Search Implementation**
   - Uses pgvector's nearest neighbor search with cosine similarity
   - Supports both vector and text-based queries
   - Handles soft deletes and additional query constraints
   - Maintains proper model relationships in search results

6. **Content Processing**
   - Converts model attributes to labeled text format
   - Handles nested arrays and various data types
   - Supports customizable data formatting

### Design Decisions

1. **Handler-Based Architecture**
   - Each embedding provider has its own handler configuration
   - Handlers specify their storage requirements (table name, dimensions)
   - Allows for multiple embedding models with different dimensions
   - Easy to switch between handlers via configuration

2. **One-to-One Relationship**
   - Each model instance has exactly one vector embedding
   - For large content, it's recommended to chunk data into separate models
   - Example: Blog posts should be split into `BlogPostChunk` models for optimal search

3. **Caching Strategy**
   - Uses content hashing to prevent unnecessary embedding updates
   - Stores content hash alongside vectors for quick comparison

4. **Soft Delete Handling**
   - Leverages database joins instead of duplicating soft delete state
   - When soft deletes are enabled, embeddings table is joined with parent table
   - This ensures consistency and avoids redundant soft delete tracking
   - More efficient than traditional Scout engines which must maintain separate soft delete states

### Current Features

- ✅ Configurable embedding handlers
- ✅ Dynamic table names and dimensions
- ✅ Vector generation and storage
- ✅ Nearest neighbor search
- ✅ Content hashing for efficient updates
- ✅ Soft delete support
- ✅ Scout metadata integration
- ✅ Polymorphic relationships
- ✅ Lazy loading support

### Upcoming Features

- Pagination support
- Delete method implementation
- Total count handling
- Enhanced ID mapping
- Additional embedding handlers

This package provides a robust solution for implementing vector search in Laravel applications while maintaining the familiar Scout interface and leveraging PostgreSQL's vector capabilities. The handler-based architecture allows for easy extension and configuration of different embedding providers while maintaining consistent search functionality.