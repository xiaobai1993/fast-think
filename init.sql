######## 导入作者数据
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for author
-- ----------------------------
DROP TABLE IF EXISTS `author`;
CREATE TABLE `author` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '作者id',
  `name` varchar(25) NOT NULL DEFAULT '' COMMENT '作者名字',
  `info` varchar(255) NOT NULL DEFAULT '' COMMENT '基本信息',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COMMENT='作者';

-- ----------------------------
-- Records of author
-- ----------------------------
BEGIN;
INSERT INTO `author` VALUES (1, '罗贯中', '小说家');
INSERT INTO `author` VALUES (2, '吴承恩', '小说家');
INSERT INTO `author` VALUES (3, 'Dennis M. Ritchie', 'C语言发明者');
INSERT INTO `author` VALUES (4, 'Brian W. Kernighan', '计算机专家');
COMMIT;

SET FOREIGN_KEY_CHECKS = 1;

############### 导入图书的数据


SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for book
-- ----------------------------
DROP TABLE IF EXISTS `book`;
CREATE TABLE `book` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '图书id',
  `title` varchar(25) NOT NULL DEFAULT '' COMMENT '图书标题',
  `press_id` int(11) NOT NULL DEFAULT '0' COMMENT '出版社id',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COMMENT='图书';

-- ----------------------------
-- Records of book
-- ----------------------------
BEGIN;
INSERT INTO `book` VALUES (1, '三国演义', 1);
INSERT INTO `book` VALUES (2, '西游记', 2);
INSERT INTO `book` VALUES (3, 'C程序设计语言', 1);
COMMIT;
SET FOREIGN_KEY_CHECKS = 1;

##### 导入出版社信息
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for press
-- ----------------------------
DROP TABLE IF EXISTS `press`;
CREATE TABLE `press` (
  `id` int(11) NOT NULL COMMENT '出版社id',
  `name` varchar(25) NOT NULL DEFAULT '' COMMENT '出版社名字',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='出版社';

-- ----------------------------
-- Records of press
-- ----------------------------
BEGIN;
INSERT INTO `press` VALUES (1, '中国人民出版社');
INSERT INTO `press` VALUES (2, '北方工业出版社');
COMMIT;

SET FOREIGN_KEY_CHECKS = 1;


############### 导入图书作者关系数据

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for author_book_relation
-- ----------------------------
DROP TABLE IF EXISTS `author_book_relation`;
CREATE TABLE `author_book_relation` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '图书id',
  `author_id` int(11) NOT NULL COMMENT '作者id',
  `book_id` int(11) NOT NULL COMMENT '图书id',
  `is_main` int(11) NOT NULL COMMENT '是否是主要作者 1示是，0示不是',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COMMENT='作者图书关系';

-- ----------------------------
-- Records of author_book_relation
-- ----------------------------
BEGIN;
INSERT INTO `author_book_relation` VALUES (1, 1, 1, 1);
INSERT INTO `author_book_relation` VALUES (2, 2, 2, 1);
INSERT INTO `author_book_relation` VALUES (3, 3, 3, 1);
INSERT INTO `author_book_relation` VALUES (4, 4, 3, 0);
COMMIT;


###### 导出
SET FOREIGN_KEY_CHECKS = 1;
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
-- ----------------------------
-- Table structure for book_comment
-- ----------------------------
DROP TABLE IF EXISTS `book_comment`;
CREATE TABLE `book_comment` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '评论的id',
  `book_id` int(11) NOT NULL COMMENT '图书的id',
  `content` text NOT NULL COMMENT '图书评论的内容',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COMMENT='图书评论';

-- ----------------------------
-- Records of book_comment
-- ----------------------------
BEGIN;
INSERT INTO `book_comment` VALUES (1, 3, '非常不错的C语言教材');
INSERT INTO `book_comment` VALUES (2, 3, '太好了');
INSERT INTO `book_comment` VALUES (3, 1, '不错的小说');
COMMIT;

SET FOREIGN_KEY_CHECKS = 1;


