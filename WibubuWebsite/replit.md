# Wibubu E-commerce Platform

## Overview

Wibubu is a modern e-commerce website designed with a "Shy Rainbow" pastel color scheme, inspired by the Mykingdom website layout. The platform serves as a comprehensive online marketplace featuring product catalogs, shopping cart functionality, user authentication, promotional campaigns, and real-time customer support through integrated chat functionality. The system is built to support multiple user roles including administrators, sales staff, and customers, each with appropriate access levels and capabilities.

## User Preferences

Preferred communication style: Simple, everyday language.

## System Architecture

### Frontend Architecture
The frontend is built using vanilla HTML, CSS, and JavaScript with a focus on modern web standards and responsive design. The architecture follows a component-based approach with modular JavaScript files handling specific functionality:

- **CSS Design System**: Uses CSS custom properties (CSS variables) to implement a comprehensive design token system with the "Shy Rainbow" pastel color palette
- **Theme System**: Implements dark/light mode switching with localStorage persistence
- **Modular JavaScript**: Separate modules for cart management, general UI interactions, and theme handling
- **Responsive Design**: Mobile-first approach ensuring optimal experience across all device sizes

### Backend Architecture
The backend utilizes PHP with the Laravel framework, providing:

- **MVC Pattern**: Separation of concerns through Laravel's Model-View-Controller architecture
- **RESTful APIs**: Structured API endpoints for frontend-backend communication
- **Authentication System**: Laravel's built-in authentication for user management
- **Role-based Access Control**: Multi-tier permission system for Admin, Sales Staff, and Customer roles

### Data Management
- **Local Storage**: Client-side cart persistence using browser localStorage
- **Session Management**: Server-side session handling for user authentication and chat history
- **Database Schema**: MySQL database design supporting products, categories, users, orders, promotions, and chat messages

### Key Features Implementation
- **Shopping Cart**: Client-side cart management with add, remove, and quantity update functionality
- **Theme Toggle**: Dark/light mode with system preference detection and user override
- **Real-time Chat**: Interactive customer support chat with message history persistence
- **Product Management**: Comprehensive product catalog with categories, search, and filtering
- **Promotion System**: Flash sales and discount management with time-based activation
- **Responsive Navigation**: Adaptive menu system with proper routing between pages

## External Dependencies

### Frontend Dependencies
- **Google Fonts API**: Poppins and Inter font families for modern typography
- **Browser APIs**: localStorage for cart and theme persistence, CSS custom properties for theming

### Backend Dependencies
- **Laravel Framework**: PHP web application framework for robust backend functionality
- **MySQL Database**: Relational database management system for data persistence
- **PHP**: Server-side scripting language for backend logic

### Third-party Integrations
- **Payment Gateway**: Integration planned for checkout and payment processing
- **Image Hosting**: Asset management for product images and media files
- **Chat System**: Real-time messaging functionality with database storage for chat history

### Development Tools
- **Responsive Design**: CSS Grid and Flexbox for layout management
- **Modern JavaScript**: ES6+ features for enhanced functionality
- **CSS Preprocessing**: Advanced CSS techniques using custom properties and modern selectors