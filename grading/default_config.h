#ifndef __DEFAULT_CONFIG_H__
#define __DEFAULT_CONFIG_H__

#include "TestCase.h"

extern const char *GLOBAL_config_json_string;  // defined in json_generated.cpp

// ========================================================================================

#define ASSIGNMENT_MESSAGE ""

// ========================================================================================

#define MAX_NUM_SUBMISSIONS 20
#define SUBMISSION_PENALTY 5
#define MAX_SUBMISSION_SIZE 100000      // 100 KB submitted files size
#define PART_NAMES { }

// ========================================================================================
// ========================================================================================

extern const std::map<int,rlim_t> default_limits;
const std::map<int,rlim_t> default_limits = 
  { 
    { RLIMIT_CPU,        10                }, // 10 seconds per test
    { RLIMIT_FSIZE,      100*1000          }, // 100 KB created file size
    { RLIMIT_DATA,       500*1000*1000     }, // 500 MB heap
    { RLIMIT_STACK,      500*1000*1000     }, // 500 MB stack   }, // FIXME: could make smaller, 1 MB per thread 
    { RLIMIT_CORE,       0                 }, // don't allow core files 
    { RLIMIT_RSS,        RLIM_INFINITY     }, //                   }, // FIXME: deprecated, set to 0? 
    { RLIMIT_NPROC,      0                 }, // no additional processes  
    { RLIMIT_NOFILE,     100               }, // 100 file descriptors 
    { RLIMIT_MEMLOCK,    500*1000*1000     }, // 500 MB RAM             }, // FIXME: set to 0
    { RLIMIT_AS,         1*1000*1000*1000  }, // 1 GB virtual memory address space 
    { RLIMIT_LOCKS,      100               }, // 100 files open  }, // FIXME: set to 0 (except java?) 
    { RLIMIT_SIGPENDING, 0                 }, // 
    { RLIMIT_MSGQUEUE,   0                 }, //  
    { RLIMIT_NICE,       RLIM_INFINITY     }, // 
    { RLIMIT_RTPRIO,     0                 }, // 
    { RLIMIT_RTTIME,     0                 }  // 
  };

// ========================================================================================
// ========================================================================================

#endif // __DEFAULT_CONFIG_H__
