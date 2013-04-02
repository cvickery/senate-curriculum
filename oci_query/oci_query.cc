//  oci_query.cc
/*  Reads a query string, runs the query, writes the result set as a JSON-encoded
 *  array of objects. Each object corresponds to one row of the result set
 *  returned by the query, with the column names as property names and the
 *  column values as property values.
 *
 *  2012-12-12
 *    Support for multi-line query strings.
 */

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <json/json.h>
#include <ocilib.h>
#include "ocilib_types.h"

#define BUF_SIZE 8192

//  err_handler()
//  ---------------------------------------------------------------------------
/*  OCILIB error handler. Displays the OCI Error string and exits the app.
 */
  void err_handler(OCI_Error *err)
  {
      fprintf(stderr, "oci_query: %s\n", OCI_ErrorGetString(err));
      exit(EXIT_FAILURE);
  }

//  main()
//  ---------------------------------------------------------------------------
/*  Read query string from stdin, sanitize , make db connection, run query,
 *  JSON-encode the result set, write to stdout.
 */
  int main(int argc, char *argv[], char *envp[])
  {
    //  Check command line arguments
    if (1 != argc)
    {
      fprintf(stderr, "This filter takes no arguments.\n");
      exit(EXIT_FAILURE);
    }

    //  Get query string from stdin
    char query_str[BUF_SIZE] = "";
    char query_part[BUF_SIZE];
    while (true)
    {
      if (fgets(query_part, BUF_SIZE - 1, stdin))
      {
        if (strlen(query_part) + strlen(query_str) < BUF_SIZE - 1)
        {
          strcat(query_str, query_part);
        }
        else
        {
          fprintf(stderr, "Query string too long\n");
          exit(EXIT_FAILURE);
        }
      }
      else
      {
        break;
      }
    }

    //  Sanitize:
    //    Only select queries allowed
    //    No semicolons; no SQL comments
    if (strncasecmp(query_str, "select ", 7))
    {
      fprintf(stderr, "oci_query: invalid query type\n");
      exit(EXIT_FAILURE);
    }
    if (strchr(query_str, ';') || strstr(query_str, "--"))
    {
      fprintf(stderr, "oci_query: invalid query structure\n");
      exit(EXIT_FAILURE);
    }

    //  Set up the query
    if (!OCI_Initialize(err_handler, NULL, OCI_ENV_DEFAULT))
    {
      fprintf(stderr, "oci_query: initialization failed\n");
      exit(EXIT_FAILURE);
    }
    //  Get the ConnectionCreate parameters
    //    The first three lines contain the db connection string,
    //    the username, and the password. Blank and comment lines
    //    (starting with #) are ignored. Blank lines are defined
    //    as lines that start with a linefeed character; 'doze and
    //    'ac files won't work because it would have made the while
    //    statemets too long to fit on one line.
    char path[BUF_SIZE];
    strncpy(path, getenv("ORACLE_HOME"), BUF_SIZE - 1);
    strncat(path, "connection_parameters", BUF_SIZE - 1);
    FILE *fp = fopen(path, "r");
    if (! fp)
    {
      perror(path);
      fprintf(stderr, "oci_query: unable to read connection parameters\n");
      exit(EXIT_FAILURE);
    }
    char db[BUF_SIZE] = "\n", user[BUF_SIZE] = "\n", pass[BUF_SIZE] = "\n";
    while ((db[0]   == '\n') || (db[0]   == '#')) fgets(db,   BUF_SIZE - 1, fp);
    while ((user[0] == '\n') || (user[0] == '#')) fgets(user, BUF_SIZE - 1, fp);
    while ((pass[0] == '\n') || (pass[0] == '#')) fgets(pass, BUF_SIZE - 1, fp);
    db[strlen(db) - 1]      = '\0';
    user[strlen(user) - 1]  = '\0';
    pass[strlen(pass) - 1]  = '\0';

    //  Create the connection and run the query
    OCI_Connection  *cn = OCI_ConnectionCreate(db, user, pass, OCI_SESSION_DEFAULT);
    if ( !cn )
    {
      fprintf(stderr, "oci_query: connection failed\n");
      exit(EXIT_FAILURE);
    }

    OCI_Statement   *st = OCI_StatementCreate(cn);
    if ( !st )
    {
      fprintf(stderr, "oci_query: create statement failed\n");
      exit(EXIT_FAILURE);
    }

    //  Run the query
    printf("%s\n", query_str);
    if (! OCI_ExecuteStmt(st, query_str))
    {
      fprintf(stderr, "oci_query: execute stmt failed\n");
      exit(EXIT_FAILURE);
    }

    OCI_Resultset   *rs = OCI_GetResultset(st);
    if ( !rs )
    {
      fprintf(stderr, "oci_query: get result set failed\n");
      exit(EXIT_FAILURE);
    }

    //  Capture the column names
    int num_cols = OCI_GetColumnCount(rs);
    int col_num = 0;
    char *column_names[num_cols];
    const char *column_name;
    for (int i = 1; i <= num_cols; i++)
    {
      OCI_Column *col = OCI_GetColumn(rs, i);
      column_name = OCI_GetColumnName(col);
      column_names[col_num] = (char *)malloc(strlen(column_name) + 1);
      strlcpy(column_names[col_num++], column_name, strlen(column_name) + 1);
    }

    //  Declare the json objects to be used to construct the output string
    json_object *return_array = json_object_new_array();
    json_object *object, *value;
    char *name;

    //  Turn the result set into an array of json-encoded objects
    while (OCI_FetchNext(rs))
    {
      //  Each resultset row becomes an object in the array returned
      object = json_object_new_object();
      for (int i = 0; i < num_cols; i++)
      {
        name = column_names[i];
        const char *v = OCI_GetString(rs, i + 1);
        if (NULL == v) v = "";
        value = json_object_new_string(v);
        json_object_object_add(object, name, value);
      }
      json_object_array_add(return_array, object);
    }

    //  Output the json-encoded array of objects
    printf("%s\n", json_object_to_json_string(return_array));
    OCI_Cleanup();
    return EXIT_SUCCESS;
  }

