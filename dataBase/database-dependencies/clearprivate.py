import mysql.connector


def connect_to_db():
    conn = mysql.connector.connect(
        host="localhost", user="root", password="", database="messaging_app"
    )
    return conn

conn = connect_to_db()
cursor = conn.cursor()

cursor.execute("DELETE FROM private_messages")
conn.commit()

print("Deleted Messages")