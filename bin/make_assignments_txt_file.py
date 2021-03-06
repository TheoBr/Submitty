#!/usr/bin/env python

import json
import sys
import os
import stat


# USAGE
# make_assignments_txt_file.py   <path to forms directory>   <path to ASSIGNMENTS.txt file>


#####################################
# CHECK ARGUMENTS
if (len(sys.argv)) != 3:
    raise SystemExit("ERROR! WRONG NUMBER OF ARGUMENTS!")


#####################################
# OPEN ALL FILES IN THE FORMS DIRECTORIES
with open (sys.argv[2],'w') as outfile:
    for filename in os.listdir(sys.argv[1]):
        length = len(filename)
        extension = filename[length-5:length]
        if (extension != ".json") :
            continue
        json_filename = os.path.join (sys.argv[1],filename)
        if os.path.isfile(json_filename):
            with open (json_filename,'r') as infile:
                obj = json.load(infile)
        else: 
            sys.exit(1)

        # ONLY ELECTRONIC GRADEABLES HAVE A CONFIG PATH
        if "config_path" in obj:
            id = obj["gradeable_id"]
            config_path = obj["config_path"]
            dirs = sys.argv[1].split("/")
            semester=dirs[len(dirs)-4]
            course=dirs[len(dirs)-3]
            outfile.write("build_homework  "+config_path+"  "+semester+"  "+course+"  "+id+"\n")


#####################################
# SET PERMISSION ON ASSIGNMENTS.txt file
try:
    os.chmod(sys.argv[2], stat.S_IRUSR | stat.S_IRGRP | stat.S_IWUSR | stat.S_IWGRP )
except OSError:
    pass
