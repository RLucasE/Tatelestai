
Description

A comprehensive web-based system designed to connect food establishments with customers to efficiently manage food surplus. This platform helps reduce food waste by allowing establishments to sell excess food products at accessible prices, creating a win-win situation for businesses and consumers while promoting sustainable practices in the food industry.


Development Process
Analysis and Planning
The development of this project began with a thorough analysis of the problem space and the definition of clear objectives. Using the MoSCoW method helped me prioritize the main problems to solve based on difficulty and value, focusing first on those that were easier to implement but provided the highest impact.


Frontend: Vue.js with Vue Router for SPA navigation and Axios for API communication, 
Backend: Laravel framework providing RESTful API endpoints and business logic
Database: MySQL for data persistence
Authentication: Laravel Sanctum for secure token-based authentication
The architecture follows a clear separation of concerns with:


Backend handling data persistence, business logic, and API endpoints
Frontend focused on responsive UI, state management, and user experience
RESTful API communication between frontend and backend
Key Features Implemented
User Management


Registration with role selection
State-based user lifecycle (selecting, registering, waiting confirmation, active, inactive)
Authentication and authorization
Establishment Management


Creation and editing of establishment profiles
Association with establishment types
Address and contact information
Offer Management


Creation, editing, and deletion of food surplus offers
Setting prices, quantities, and availability periods
Image upload for offer representation
Sales Process


Shopping cart functionality
Order placement and confirmation
Purchase history
Admin Dashboard


User management and moderation
Establishment approval process
System statistics and reporting
Future Goals
Search Optimization
Planning to integrate Elasticsearch to provide advanced search capabilities:


Full-text search across offers and establishments
Faceted search with multiple filters
Geolocation-based search to find nearby offers
Fuzzy matching for typo tolerance
Real-time search suggestions
Google API Integration
Future versions will incorporate various Google APIs:
Google Maps for establishment location display and directions
Google Places API for address autocomplete
Google Sign-In for simplified authentication
Google Analytics for enhanced usage tracking
Additional Planned Features
Mobile application development (iOS and Android)
Machine learning for personalized recommendations
Integration with payment gateways for online transactions
Expansion of analytics capabilities for business intelligence
Establishment verification system using official documentation
Community features to promote sustainability initiatives