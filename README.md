# Multilingual Website with Symfony

## Example of a website with a multilingual database - Made with :heart: by @TheJulienM
### Note : This project is made with [DoctrineBehaviors](https://github.com/KnpLabs/DoctrineBehaviors)

Requirements :
- `PHP8+`
- `Symfony 5.4+`
- `MySQL Database (or other as you prefer)`
- `A web server`

## How to install this project :
1) Clone the project :
    - `git clone git@github.com:TheJulienM/multilingual_website_symfony.git`
2) Install the necessary dependencies for this project :
    - `composer install`
3) Edit your .env with your own configuration
4) Create your database with the following command :
   - `php bin/console d:d:c`
5) Update the database 
   - `php bin/console d:s:u -f`
6) Start the project :
   - `symfony server:start`
7) Open you favourite web browser and go to [http://localhost:8000/](http://localhost:8000/)
8) Enjoy !


## How to use the project :
1) Create your article(s)
   - [http://localhost:8000/create-article](http://localhost:8000/create-article)
2) Create the translations :
   - [http://localhost:8000/translate-article/en/1](http://localhost:8000/translate-article/en/1) (_example_) 
3) Read your articles in the different languages (English, French, German and Spanish by default) !
   - [http://localhost:8000/read-articles](http://localhost:8000/read-articles) 
4) You can add other languages with the [http://localhost:8000/create-language](http://localhost:8000/create-language)