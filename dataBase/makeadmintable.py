import mysql.connector
from mysql.connector import errorcode

# Database connection configuration
config = {
    'user': 'root',
    'password': '',
    'host': 'localhost',
    'database': 'messaging_app'
}

try:
    # Connect to the database
    conn = mysql.connector.connect(**config)
    cursor = conn.cursor()

    # Create the admins table
    cursor.execute('''
    CREATE TABLE IF NOT EXISTS admins (
        username VARCHAR(255) PRIMARY KEY,
        role ENUM('mod', 'admin') NOT NULL
    )
    ''')

    # Commit the changes
    conn.commit()
    print("Admins table created successfully.")

except mysql.connector.Error as err:
    if err.errno == errorcode.ER_ACCESS_DENIED_ERROR:
        print("Something is wrong with your user name or password")
    elif err.errno == errorcode.ER_BAD_DB_ERROR:
        print("Database does not exist")
    else:
        print(err)
finally:
    # Close the connection
    if conn.is_connected():
        cursor.close()
        conn.close()