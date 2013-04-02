//  test_env.cc
/*  Answers the question of what environment variables are set when a C program
 *  executes. Used with test_env.php to make sure the php exec() function can
 *  set up environment variables for a C program it exec()s.
 */
#include <stdio.h>
#include <stdlib.h>
int main(int argc, char *argv[], char *envp[])
{
  printf("%s\n", getenv("ORACLE_HOME"));
  exit(EXIT_SUCCESS);
}

