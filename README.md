<img width="1024" height="267" alt="logo" src="https://github.com/user-attachments/assets/a5ef7417-49cd-4f5f-96cf-74fd51c89e16" />

# Avalanche Old Site Source Code
This Repo contains the old alanbloxxr.xyz Site Source code, Google has flagged our site as "Not secure" and people have been claiming it as a Key logger. I have open sourced this to prove people that it is not. a key logger.

# Currently want to implement:
- [ ] GameScripts and Joining
- [ ] Rendering
- [x] 2FA (this was a pain in the ass and decided to risk it.)
- [ ] Forgot Password (half done)
- [ ] Create Games Page (done a quarter of it but its EXTREMELY buggy)
- [ ] Multi-Client (revivals with 2-5 client year support)


# WARNING!
This source is buggy and i do not recommend it for revivals, But can be implemented/fixed even further. Also keep in mind that some files have rbxl implementation when its **not.** because this bullshit pisses me off and people keep bugging me to add create

# How to use/Step 1
Download the source code by pressing the green button "Code" and press "Download ZIP" Once its finished, Extract the file using whatever file extracter you want, Now download a webserver (not an actual one a localhost one) Such as XAMPP, Laragon, WAMP, or any other one you prefer, I'll leave some links here:

[XAMPP](https://www.apachefriends.org/download.html)
[Laragon](https://laragon.org/download)
[WAMP](https://wampserver.aviatechno.net/)
[UwAmp](https://www.uwamp.com/en/?page=download) 
(for UwAmp i'd rather recommend installing either the .RAR file or .ZIP)

# Step 2
Get the extracted source code and put it in these folders depending on your webserver:

For **UwAmp**: Go to your extracted UwAmp client, Go to the folder "www" And delete everything there, Then put the source code into that folder.

For **XAMPP**: On the XAMPP Control panel, Press on "Explorer" on the right side bar, Then find the folder "htdocs" and delete everything there, Then put the source code into that folder.

For **WAMP**: Locate the WampServer www directory: Open File Explorer (Windows Explorer) and navigate to the drive where WampServer is installed (usually C:). Find the WampServer installation folder, typically named wamp or wamp64. Inside that folder, locate the www directory (e.g., C:\wamp64\www). Delete any file from there and its Best to create a Folder INSIDE the "www" directory (its optional but HIGHLY recommended) Then copy the source code into that folder.

For **Laragon**: In the laragon Ui, Click the "Root" Button on the bottom, You will be instantly directed to the "www" folder. Delete any file from there, Then put the source code into that folder.

# Step 3
Go to your webserver UI, and start the MySQL Server, Now to import the "avalanche.sql" file i want you to do one of these guides on how to import sql's to your database:

For **MySQL**: Open your terminal or command prompt, To create the target database (if it doesn’t exist yet):
Run this command:  
```
mysql -u yourusername -p
```
Enter your username and password like this: (type your actual MySQL username after -u and press Enter when prompted for the password) Inside the MySQL prompt, type: 
```
CREATE DATABASE avalanche;
```
Then type: EXIT. To import the SQL file, Run this command:
```
mysql -u yourusername -p yourdatabasename < path/to/yoursqlfile.sql
```
Enter your username and password like this: (replace yourusername, yourdatabasename, and the file path with your actual details)
**NOTE THAT THIS METHOD IS FOR LARGE FILES BUT CAN ALSO BE DONE WITH SMALLER FILES! IF YOU WANT SOMETHING EASIER FOLLOW THE PHPMYADMIN GUIDE ON THE BOTTOM OF THIS**

For **phpMyAdmin:** Keep your danm webserver on! Go to your browser and search for "localhost/phpmyadmin", Login with your username (default is root and theres not password, but i **ADVISE** you to get a password for your webserver.) Create a Database called "avalanche" And then go to the sidebar and press "avalanche" Then on the middle page, go to the topbar that says "Import", Drag and drop or press on the "Import" button or "Select File" (i dont really know man) and go to the "www" or "htdocs" Folder and find "avalanche.sql", Select that, go down and press import.

For **PostgreSQL:** Open your terminal or command prompt, To create the target database (if it doesn’t exist yet): Run this command:
```
psql -U yourusername -d avalanche
```
Enter your username and password like this: (type your actual PostgreSQL username after -U and press Enter when prompted for the password) Inside the psql prompt, type:
```
CREATE DATABASE avalanche;
```
Then type: "\q" To import the SQL file: Run this command:
```
psql -U yourusername -d avalanche < path/to/yoursqlfile.sql
```
Enter your username and password like this: (replace yourusername, and the file path with your actual details) 
**AGAIN, THIS IS FOR LARGE FILES, FOR SOMETHING EASIER, DO THE PHPMYADMIN GUIDE ON THE TOP OF THIS GUIDE.**

For **HeidiSQL:** this ones my favourtie sql!!! Open HeidiSQL. Create or open your connection to the database server and log in with your credentials. If the target database doesn’t exist yet: Right-click on Databases in the left panel, choose Create new, enter the name, and confirm. Select (click) the target database in the left panel to activate it. Go to the top menu and click File > Load SQL file (or Run SQL file in some versions). Browse to your .sql file, select it, and click Open.
If a prompt appears, choose Run file directly (recommended for most imports) or Load into editor, then click the Execute button (play/lightning icon) or press F9.

# Step 4 (my fingers are BLEEDING)
Go to the "www" or "htdocs" Folder of your webserver, Go to "config.php" And find this line:
```
$db_host = 'localhost';
$db_name = 'avalanche';
$db_user = 'root_or_your_webserver_username';
$db_pass = 'no_password_or_your_webserver_password';
```
Replace your username and your password (default is no password but LIKE I SAID, YOU NEED A PASSWORD!!!!), After that, press Ctrl+S (Windows) or Cmd+S (macOS) to Save.

# Step 5
Start your webserver, Start Apache, MySQL or whatever one. Signup to a Account, and there you go! 

# Have Fun!
