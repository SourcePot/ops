# Datapool Open Patent Service add-on (Processor interface)
## Setting-up a testpage
1. Run composer ``composer create-project sourcepot/ops {add your target directory here}`` on your server. This will create among other things the **../www/**-subdirectory, which is the document root and 
2. Create the database and a database user, e.g. a user and database named "webpage".
3. Set the database collation to **utf8_unicode_ci**.
4. Call the webpage through a web browser. This will create an error message since the database access needs to be set up. Check the error log which can be found in the **../debugging/**-subdirectory.  Each error generates a JSON-file containing the error details. Calling the webpage also creates the file **../setup/Database/connect.json** which contains the database access credentials. Use a text editor to update or match the credentials with the database user credentials. 
5. Refresh the webpage. This will create an initial admin user account. The user credentials of this account can be found in **../setup/User/initAdminAccount.json**.  With the credentials from this file you should be able the login for the first time.
