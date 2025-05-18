CRM system for processing and analyzing establishment tickets using Laravel, PHP, and MariaDB. The application includes:

1. **Database Structure**:

- Created models and migrations for Establishments, Products, Tickets, TicketItems, and ItemProductMappings

- Set up relationships between these entities

2. **Ticket Processing System**:

- Implemented a command to process tickets from the NEW directory

- Added OCR capabilities using Tesseract and image preprocessing with Intervention/Image

- Created AI analysis logic to identify establishments, extract items and prices

- Implemented learning capabilities to improve recognition over time

3. **Web Interface**:

- Created controllers for all entities

- Built dashboard with key metrics and charts

- Implemented CRUD operations for establishments, products, and tickets

- Added product-to-item mapping functionality

- Designed template management for better ticket recognition

4. **Key Features**:

- Automatic processing of tickets with OCR and AI

- Manual review workflow for uncertain tickets

- Product master catalog with mapping to ticket items

- Learning system to improve recognition over time

- Export capabilities for product data

The system allows for both automated and manual ticket processing, with a feedback loop for continuous improvement. Users can view, edit, and review tickets, manage establishments and products, and see analytics about their data.

To start using the system:

1. Place ticket images in the `/tickets/NEW` directory

2. Run the processing command: `php artisan tickets:process`

3. Access the web interface to review results and manage data

For best results:

- Create products in the system before processing tickets

- Review and confirm mappings to improve future recognition

- Configure establishment templates for better item extraction

You can further enhance the system by adding authentication, implementing a more advanced AI model, or adding more detailed reporting capabilities.
