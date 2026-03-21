<img width="1024" height="267" alt="logo" src="https://github.com/user-attachments/assets/a5ef7417-49cd-4f5f-96cf-74fd51c89e16" />

# AvalancheSiteSource
This Repo contains the alanbloxxr.xyz Site Source code, Google has flagged our site as "Not secure" and people have been claiming it as a Key logger. I have open sourced this to prove people that it is not. a key logger.

# Currently want to implement:

# WARNING!
This source is buggy and i do not recommend it for revivals, But can be implemented/fixed even further

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
