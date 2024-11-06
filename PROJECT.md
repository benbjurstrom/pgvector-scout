# PgVector Scout Package for Laravel

This package provides a Laravel Scout engine for the pgvector PostgreSQL extension. Laravel Scout is a first-party package for Laravel that is used for searching models using vector embeddings.

## Architecture Overview

Scout is an excellent choice for this implementation since it automatically uses model observers to keep the searchable data up to date. While traditionally Scout engines store data in separate search systems like Typesense or Meilisearch, this package leverages PostgreSQL's pgvector extension to store and search vector embeddings directly in the database.

### Key Components

1. **Centralized Vector Storage**
   - All vectors and their metadata are stored in a single `embeddings` table
   - Uses Laravel's polymorphic relationships to associate vectors with models
   - Schema defined in `create_embeddings_table.php` migration

2. **Embedding Model**
   - The package provides an `Embedding` model for managing vectors
   - Creates polymorphic relationships to searchable models
   - Stores vector data, content hash, and embedding model information

3. **Vector Updates**
   - Implements efficient vector updates using content hashing
   - Only generates new embeddings when content changes
   - Supports Laravel Scout's queueing system for async vector generation
   - Integrates with external embedding services (e.g., OpenAI)

4. **Search Implementation**
   - Uses pgvector's nearest neighbor search with cosine similarity
   - Supports both vector and text-based queries
   - Handles soft deletes and additional query constraints
   - Maintains proper model relationships in search results

5. **Content Processing**
   - Converts model attributes to labeled text format
   - Handles nested arrays and various data types
   - Supports customizable data formatting

### Design Decisions

1. **One-to-One Relationship**
   - Each model instance has exactly one vector embedding
   - For large content, it's recommended to chunk data into separate models
   - Example: Blog posts should be split into `BlogPostChunk` models for optimal search

2. **Caching Strategy**
   - Uses content hashing to prevent unnecessary embedding updates
   - Stores content hash alongside vectors for quick comparison

3. **Soft Delete Handling**
   - Leverages database joins instead of duplicating soft delete state
   - When soft deletes are enabled, embeddings table is joined with parent table
   - This ensures consistency and avoids redundant soft delete tracking
   - More efficient than traditional Scout engines which must maintain separate soft delete states

4. **Extensibility**
   - Configurable embedding models and actions
   - Supports custom vector generation implementations
   - Integrates with Laravel's existing Scout features

### Current Features

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

This package provides a robust solution for implementing vector search in Laravel applications while maintaining the familiar Scout interface and leveraging PostgreSQL's vector capabilities.