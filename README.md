# GraphQLDemo

A Symfony project demonstrating the use of GraphQL.

---

## 1) Requirements
1. Docker
2. Docker Compose
3. (Windows) WSL2

---

## 2) Installation / Run
1. Clone the Repository
   ```
   git clone https://github.com/LuigiG34/GraphQLDemo
   cd GraphQLDemo
   ```

2. Start Docker Containers
   ```
   docker compose up -d --build
   ```

3. Install PHP Dependencies
   ```
   docker compose exec php composer install
   ```

4. Create Database
   ```
   docker compose exec php php bin/console doctrine:database:create
   ```

5. Generate Database Migration
   ```
   docker compose exec php php bin/console make:migration
   ```

6. Apply Database Migration
   ```
   docker compose exec php php bin/console doctrine:migrations:migrate
   ```

7. Load DataFixtures
   ```
   docker compose exec php php bin/console doctrine:fixtures:load
   ```

8. Access the Web Application
   - View all books `http://localhost:8000/books`
   - View all authors `http://localhost:8000/authors`

*From those 2 routes you'll be able to access the other ones.*

---

## 3) Syntax Basics

1. Query (Read data)
   ```graphql
   query {
      books(first: 5) {
         edges {
            node {
            id
            title
            author {
               name
            }
            }
         }
      }
   }
   ```
   
2. Mutation (create/update/delete)
   ```graphql
   mutation {
      createBook(input: {
         title: "Dune"
         publishedAt: "1965-01-01T00:00:00+00:00"
         author: "/api/authors/1"
      }) {
         book {
            id
            title
         }
      }
   }
   ```

3. Variables (dynamix queries)
   ```graphql
   query($n:Int!) {
      authors(first: $n) {
         edges {
            node { id name }
         }
      }
   }
   ```

   ```json
   {
      "n": 3
   }
   ```

GraphQL Playground : `http://localhost:8000/api/graphql`