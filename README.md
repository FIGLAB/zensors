# Original Zensors Research Repository

This is the research codebase for the preliminary work on Zensors (ca. 2014). It is not currently maintained.

Citation to the reference paper:
```
@inproceedings {zensors,
  author={Laput, G. and Lasecki, W.~S. and Wiese, J. and Xiao, R. and Bigham, J.~P. and Harrison, C.},
  title={Zensors: Adaptive, Rapidly Deployable, Human-Intelligent Sensor Feeds},
  booktitle={Proceedings of the SIGCHI Conference on Human Factors in Computing Systems},
  series={CHI '15},
  year={2015},
  location={Seoul, Republic of Korea},
  numpages={10},
  publisher={ACM},
  address={New York, NY, USA},
  keywords={smart environments, sensing, human computation, computer vision, machine learning, end-user programming},
  url={http://www.gierad.com/assets/zensors/zensors.pdf},
  movie={https://www.youtube.com/watch?v=VVP9emuFsQI},
}
```

#  Setup
Here's an extremely simplified method for setting up e.g., an EC2 instance to run the server code.

1. Create a new EC2 instance with Ubuntu Server (we used 14.04 LTS)
2. `scp ec2-deployment-key.key ubuntu@<server>:.ssh/id_rsa`
3. `ssh` and perform:

        sudo apt-get update
        sudo apt-get install git python-pip python-dev python-pillow python-tornado python-pyftpdlib
        git clone https://github.com/FIGLAB/zensors.git

Once setup, you can run the Zensors Img Backend via:

        python zensors/img_backend/main.py
