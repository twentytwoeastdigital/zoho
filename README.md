# Twentytwoeastdigital

## Overview

Twentytwoeastdigital is an API wrapper for Zoho. This package provides tools for interacting the Zoho API. Currently it only supports Zoho Creator.

## Features
- Built-in Throttle Control
- Automatic Record Caching
- Bypass Cache and Caching Time
- Bulk Download Data
- Batch request bulk data
- CRUD API Commands for Popular Zoho Apps

## Coming Soon
- Zoho Inventory Wrapper
- Bulk Update Requests
- Create a GitHub Issue to Request Additional Features

## Installation

### 1. To install the package, run the following command:

    composer require twentytwoeastdigital\zoho

### 2. Add required variables to ENV
    TWENTYTWOEASTDIGITAL_ZOHO_CLIENT_ID=1000.XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
    TWENTYTWOEASTDIGITAL_ZOHO_CLIENT_SECRET=XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
    TWENTYTWOEASTDIGITAL_ZOHO_REDIRECT_URI="${APP_URL}/zoho/callback"
    TWENTYTWOEASTDIGITAL_ZOHO_CREATOR_APP_OWNER=""
    TWENTYTWOEASTDIGITAL_ZOHO_CREATOR_APP_LINK_NAME=""

### 3. Add optional variables to ENV
    Scopes? 


### 4. Make Initial OAuth Request
    In your browser visit: ${APP_URL}/zoho/authorize, approve the request. That's all!

## Usage

### Creator API v2.1

#### Create
    
    Definition: ZohoCreator::create(FORM_NAME, DATA);

    Response: Returns Zoho Response

#### Read

##### Get All / Query Records

    Definition: ZohoCreator::get(REPORT_NAME, QUERY (Optional), BYPASS_CACHE (Optional), BYPASS_DEFAULT_CACHE_TIME_IN_SECONDS (Optional));

    Example with Caching: ZohoCreator::get('Report_Name');
    Example with Caching & Query: ZohoCreator::get('Report_Name', 'Product_Name==Test&&Price>100');
    Example bypassing Cache:  ZohoCreator::get('Report_Name', 'Name', true);
    Example set Cache Time to 5 seconds: ZohoCreator::get('Report_Name', null, false, 5);

    Response: Array of Records

##### Get Records By Id

    Definition: ZohoCreator::getById(REPORT_NAME, RECORD_ID, BYPASS_CACHE (Optional), BYPASS_DEFAULT_CACHE_TIME_IN_SECONDS (Optional));

    Example with Caching: ZohoCreator::getById('Report_Name', $id);
    Example bypassing Cache:  ZohoCreator::getById('Report_Name', $id, true);
    Example set Cache Time to 5 seconds: ZohoCreator::getById('Report_Name', $id, false, 5);

    Response: Array of Record Values

#### Update

##### Update Record by ID

    Definition: ZohoCreator::update(REPORT_NAME, DATA, RECORD_ID);

    Response: Returns Zoho Response

##### Update Bulk Records

    Coming Soon

#### Delete

##### Delete Record by ID

    Definition: ZohoCreator::delete(REPORT_NAME, RECORD_ID);

    Response: Returns Zoho Response

##### Delete Bulk Records

    Coming Soon

## Contributing

We welcome contributions! Please make a pull request on GitHub.

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

## Contact

For any questions or feedback, please contact us by submitting an issue on GitHub.