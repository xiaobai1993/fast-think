CREATE TABLE `book_comment` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '评论的id',
  `book_id` int(11) NOT NULL COMMENT '图书的id',
  `content` text NOT NULL COMMENT '图书评论的内容',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COMMENT='图书评论';